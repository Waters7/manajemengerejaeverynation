<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\BatchStatus;
use App\Enums\ParticipantStatus;
use App\Enums\ProgramType;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\ClassBatch;
use App\Models\ClassParticipant;
use App\Models\ClassSession;
use App\Models\DiscipleshipProgram;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\ClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Class batches for CLASS / TRAINING / EVENT programs, including Victory Weekend.
 */
class ClassBatchController extends Controller
{
    public function __construct(
        private AccessScope $scope,
        private ClassService $classes,
    ) {}

    public function index(Request $request): View
    {
        $batches = $this->scope->classBatches(ClassBatch::query(), $request->user())
            ->with(['program.stage', 'facilitator', 'campus'])
            ->withCount(['activeParticipants', 'sessions', 'participants as completed_count' => fn ($q) => $q->where('status', ParticipantStatus::Completed->value)])
            ->when($request->filled('program'), fn ($q) => $q->where('discipleship_program_id', $request->integer('program')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->upcoming())
            ->orderBy('start_date')
            ->paginate(24)
            ->withQueryString();

        return view('admin.classes.index', [
            'batches' => $batches,
            'programs' => DiscipleshipProgram::active()->where('type', '!=', ProgramType::Book->value)->orderBy('sequence')->pluck('name', 'id'),
            'statuses' => BatchStatus::options(),
        ]);
    }

    public function victoryWeekend(Request $request): View
    {
        $program = DiscipleshipProgram::where('slug', DiscipleshipProgram::VICTORY_WEEKEND_SLUG)->first();
        $pfv = DiscipleshipProgram::where('slug', DiscipleshipProgram::PREPARING_FOR_VICTORY_SLUG)->first();

        $batches = $program
            ? $this->scope->classBatches($program->batches()->getQuery(), $request->user())
                ->with(['participants.profile', 'participants.attendances', 'sessions'])->get()
            : collect();

        $pfvStatus = $pfv
            ? MemberProgramProgress::where('discipleship_program_id', $pfv->id)
                ->whereIn('profile_id', $batches->flatMap->participants->pluck('profile_id'))
                ->pluck('status', 'profile_id')
            : collect();

        return view('admin.classes.victory-weekend', [
            'program' => $program,
            'batches' => $batches,
            'pfvStatus' => $pfvStatus,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ClassBatch::class);

        return view('admin.classes.form', $this->formData($request, new ClassBatch([
            'discipleship_program_id' => $request->integer('program') ?: null,
            'status' => BatchStatus::Planned,
            'registration_status' => 'open',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ClassBatch::class);
        $data = $this->validated($request);

        if (! $this->scope->isChurchWide($request->user())) {
            abort_unless(in_array((int) ($data['campus_id'] ?? 0), $this->scope->campusIds($request->user()), true), 403, 'Choose one of your campuses.');
        }

        $batch = ClassBatch::create($data);
        if ($request->boolean('generate_sessions')) {
            $this->classes->generateSessions($batch);
        }

        return redirect()->route('admin.classes.show', $batch)->with('status', 'Class batch created.');
    }

    public function show(Request $request, ClassBatch $batch): View
    {
        $this->authorize('view', $batch);

        $batch->load(['program', 'facilitator', 'campus', 'sessions' => fn ($q) => $q->withCount(['attendances as present_count' => fn ($a) => $a->where('status', AttendanceStatus::Present->value)])]);
        $participants = $batch->participants()->with(['profile.activeLifeGroups', 'attendances'])->get()->sortBy('profile.full_name');

        return view('admin.classes.show', [
            'batch' => $batch,
            'participants' => $participants,
            'statuses' => ParticipantStatus::options(),
            'canManage' => $request->user()->can('update', $batch),
            'candidates' => $this->scope->profiles(Profile::query(), $request->user())
                ->whereNotIn('id', $participants->pluck('profile_id'))->orderBy('full_name')->limit(800)->pluck('full_name', 'id'),
        ]);
    }

    public function edit(Request $request, ClassBatch $batch): View
    {
        $this->authorize('update', $batch);

        return view('admin.classes.form', $this->formData($request, $batch));
    }

    public function update(Request $request, ClassBatch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);
        $batch->update($this->validated($request));

        return redirect()->route('admin.classes.show', $batch)->with('status', 'Class batch updated.');
    }

    public function destroy(ClassBatch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);
        abort_if($batch->participants()->where('status', ParticipantStatus::Completed->value)->exists(), 422, 'This batch has completed participants — set it to Completed or Cancelled instead.');
        $batch->delete();

        return redirect()->route('admin.classes.index')->with('status', 'Class batch deleted.');
    }

    public function enroll(Request $request, ClassBatch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);
        $data = $request->validate(['profile_ids' => ['required', 'array'], 'profile_ids.*' => [Rule::exists('profiles', 'id')]]);

        foreach (Profile::whereIn('id', $data['profile_ids'])->get() as $profile) {
            $this->classes->enroll($batch, $profile, enforceCapacity: false);
        }

        return back()->with('status', count($data['profile_ids']).' participant(s) registered.');
    }

    public function updateParticipant(Request $request, ClassParticipant $participant): RedirectResponse
    {
        $this->authorize('update', $participant->batch);
        $data = $request->validate(['status' => ['required', Rule::enum(ParticipantStatus::class)]]);

        $this->classes->updateStatus($participant, ParticipantStatus::from($data['status']));

        return back()->with('status', 'Participant updated.');
    }

    public function storeSession(Request $request, ClassBatch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);
        $data = $this->validatedSession($request);
        $data['session_number'] ??= ($batch->sessions()->max('session_number') ?? 0) + 1;
        $batch->sessions()->create($data);

        return back()->with('status', 'Session added.');
    }

    public function generateSessions(ClassBatch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);
        $count = $this->classes->generateSessions($batch);

        return back()->with('status', $count ? "{$count} sessions generated from the curriculum." : 'This batch already has sessions.');
    }

    public function session(ClassSession $session): View
    {
        $this->authorize('view', $session->batch);

        return view('admin.classes.session', [
            'session' => $session->load(['batch.program', 'facilitator', 'attendances']),
            'participants' => $session->batch->participants()->with('profile')->where('status', '!=', ParticipantStatus::Cancelled->value)->get()->sortBy('profile.full_name'),
            'current' => $session->attendances->pluck('status', 'class_participant_id')->map->value,
            'statuses' => AttendanceStatus::options(),
            'facilitators' => Profile::members()->orderBy('full_name')->pluck('full_name', 'id'),
            'canManage' => auth()->user()->can('update', $session->batch),
        ]);
    }

    public function updateSession(Request $request, ClassSession $session): RedirectResponse
    {
        $this->authorize('update', $session->batch);
        $session->update($this->validatedSession($request));

        return back()->with('status', 'Session updated.');
    }

    public function destroySession(ClassSession $session): RedirectResponse
    {
        $this->authorize('update', $session->batch);
        $batch = $session->batch;
        $session->delete();

        return redirect()->route('admin.classes.show', $batch)->with('status', 'Session removed.');
    }

    public function attendance(Request $request, ClassSession $session): RedirectResponse
    {
        $this->authorize('update', $session->batch);
        $data = $request->validate(['attendance' => ['array'], 'attendance.*' => [Rule::enum(AttendanceStatus::class)]]);

        $this->classes->recordAttendance($session, $data['attendance'] ?? []);

        return back()->with('status', 'Attendance saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'discipleship_program_id' => ['required', Rule::exists('discipleship_programs', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'facilitator_profile_id' => ['nullable', Rule::exists('profiles', 'id')],
            'location' => ['nullable', 'string', 'max:190'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'registration_status' => ['required', Rule::in(['open', 'closed'])],
            'status' => ['required', Rule::enum(BatchStatus::class)],
            'campus_id' => ['nullable', Rule::exists('campuses', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSession(Request $request): array
    {
        return $request->validate([
            'session_number' => ['nullable', 'integer', 'min:1', 'max:200'],
            'topic' => ['required', 'string', 'max:190'],
            'session_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'facilitator_profile_id' => ['nullable', Rule::exists('profiles', 'id')],
            'room' => ['nullable', 'string', 'max:120'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ClassBatch $batch): array
    {
        $churchWide = $this->scope->isChurchWide($request->user());

        return [
            'batch' => $batch,
            'programs' => DiscipleshipProgram::active()->where('type', '!=', ProgramType::Book->value)->orderBy('sequence')->pluck('name', 'id'),
            'facilitators' => Profile::members()->orderBy('full_name')->pluck('full_name', 'id'),
            'campuses' => Campus::when(! $churchWide, fn ($q) => $q->whereIn('id', $this->scope->campusIds($request->user())))->orderBy('name')->pluck('name', 'id'),
            'statuses' => BatchStatus::options(),
        ];
    }
}
