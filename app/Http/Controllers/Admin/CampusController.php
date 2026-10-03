<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\MemberProgramProgress;
use App\Models\MinistryMember;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CAMPUS → Campus Ministry: students, leaders, LifeGroups, events, One 2 One, discipleship, volunteers.
 */
class CampusController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $campuses = Campus::query()
            ->when(! $this->scope->isChurchWide($user), fn ($q) => $q->whereIn('id', $this->scope->campusIds($user)))
            ->withCount(['students', 'lifeGroups' => fn ($q) => $q->where('status', 'active'), 'events' => fn ($q) => $q->where('starts_at', '>=', now())])
            ->with('ministers')
            ->orderBy('name')
            ->get();

        return view('admin.campuses.index', ['campuses' => $campuses]);
    }

    public function create(): View
    {
        $this->authorize('create', Campus::class);

        return view('admin.campuses.form', ['campus' => new Campus(['is_active' => true])]);
    }

    public function store(Request $request, MediaService $media): RedirectResponse
    {
        $this->authorize('create', Campus::class);
        $data = $this->validated($request);
        $data['cover_path'] = $request->file('cover') ? $media->storeImage($request->file('cover'), 'campuses')['path'] : null;

        $campus = Campus::create($data);

        return redirect()->route('admin.campuses.show', $campus)->with('status', 'Campus added.');
    }

    public function show(Campus $campus): View
    {
        $this->authorize('view', $campus);

        $studentIds = $campus->students()->pluck('id');
        $one2one = DiscipleshipProgram::one2one();

        return view('admin.campuses.show', [
            'campus' => $campus,
            'students' => $campus->students()->with(['currentStage', 'activeLifeGroups'])->orderBy('full_name')->paginate(25),
            'leaders' => User::role(Role::CampusMinistry->value)->whereHas('campuses', fn ($q) => $q->whereKey($campus->id))->get(),
            'lifeGroups' => $campus->lifeGroups()->with('leader')->withCount(['memberships as members_count' => fn ($q) => $q->where('status', 'active')])->get(),
            'events' => $campus->events()->latest('starts_at')->limit(6)->get(),
            'one2one' => MemberProgramProgress::with(['profile', 'discipler'])->whereIn('profile_id', $studentIds)
                ->where('discipleship_program_id', $one2one?->id ?? 0)->where('status', ProgressStatus::InProgress->value)->get(),
            'stages' => DiscipleshipStage::ordered()->withCount(['programs'])->get()
                ->map(fn ($stage) => ['stage' => $stage, 'count' => $campus->students()->where('current_stage_id', $stage->id)->count()]),
            'volunteers' => MinistryMember::with(['profile', 'ministry'])->whereIn('profile_id', $studentIds)->where('status', VolunteerStatus::Active->value)->get(),
        ]);
    }

    public function edit(Campus $campus): View
    {
        $this->authorize('update', $campus);

        return view('admin.campuses.form', ['campus' => $campus]);
    }

    public function update(Request $request, Campus $campus, MediaService $media): RedirectResponse
    {
        $this->authorize('update', $campus);
        $data = $this->validated($request, $campus);
        $data['cover_path'] = $media->replace($campus->cover_path, $request->file('cover'), 'campuses');
        $campus->update($data);

        return redirect()->route('admin.campuses.show', $campus)->with('status', 'Campus updated.');
    }

    public function destroy(Campus $campus): RedirectResponse
    {
        $this->authorize('delete', $campus);
        abort_if($campus->students()->exists() || $campus->lifeGroups()->exists(), 422, 'This campus still has students or LifeGroups. Set it to inactive instead.');
        $campus->delete();

        return redirect()->route('admin.campuses.index')->with('status', 'Campus removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Campus $campus = null): array
    {
        return collect($request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('campuses', 'name')->ignore($campus?->id)],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]))->except('cover')->all();
    }
}
