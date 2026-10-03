<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\DiscipleshipProgram;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\JourneyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * DISCIPLESHIP → One 2 One (the ENGAGE stage book).
 */
class One2OneController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $program = DiscipleshipProgram::one2one();

        $records = MemberProgramProgress::query()
            ->with(['profile.activeLifeGroups', 'discipler', 'chapterProgress', 'program.chapters'])
            ->where('discipleship_program_id', $program?->id ?? 0)
            ->whereHas('profile', fn ($q) => $this->scope->profiles($q, $user))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->whereIn('status', [ProgressStatus::InProgress->value, ProgressStatus::NotStarted->value]))
            ->when($request->boolean('quiet'), fn ($q) => $q->where(fn ($w) => $w->whereNull('last_activity_at')->orWhere('last_activity_at', '<', now()->subDays(14))))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('profile', fn ($p) => $p->search($request->string('q')->value())))
            ->latest('started_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.one2one.index', [
            'program' => $program,
            'records' => $records,
            'statuses' => ProgressStatus::options(),
            'people' => $this->scope->profiles(Profile::query(), $user)
                ->whereDoesntHave('programProgress', fn ($q) => $q->where('discipleship_program_id', $program?->id ?? 0))
                ->orderBy('full_name')->limit(500)->pluck('full_name', 'id'),
            'disciplers' => $this->scope->profiles(Profile::members(), $user)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function store(Request $request, JourneyService $journey): RedirectResponse
    {
        $program = DiscipleshipProgram::one2one();
        abort_unless($program, 422, 'Configure the One 2 One program in the curriculum first.');

        $data = $request->validate([
            'profile_id' => ['required', Rule::exists('profiles', 'id')],
            'discipler_profile_id' => ['nullable', 'different:profile_id', Rule::exists('profiles', 'id')],
            'started_at' => ['nullable', 'date'],
            'expected_completion_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        $profile = Profile::findOrFail($data['profile_id']);
        $this->authorize('disciple', $profile);

        $progress = $journey->startProgram(
            $profile,
            $program,
            isset($data['discipler_profile_id']) ? Profile::find($data['discipler_profile_id']) : null,
            $data['started_at'] ?? null,
            $data['expected_completion_at'] ?? null,
        );

        return redirect()->route('admin.progress.show', $progress)->with('status', "One 2 One started for {$profile->displayName()}.");
    }
}
