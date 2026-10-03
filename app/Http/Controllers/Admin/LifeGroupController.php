<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JoinRequestStatus;
use App\Enums\LeadershipStage;
use App\Enums\LifeGroupCategory;
use App\Enums\LifeGroupRole;
use App\Enums\ProgressStatus;
use App\Enums\Weekday;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LifeGroupRequest;
use App\Models\Campus;
use App\Models\LeadershipCandidate;
use App\Models\LifeGroup;
use App\Models\MemberProgramProgress;
use App\Models\PrayerRequest;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\LifeGroupService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LifeGroupController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LifeGroup::class);

        $groups = $this->scope->lifeGroups(LifeGroup::query(), $request->user())
            ->with(['leader', 'campus'])
            ->withCount(['memberships as members_count' => fn ($q) => $q->where('status', 'active'), 'pendingJoinRequests'])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('campus'), fn ($q) => $q->where('campus_id', $request->integer('campus')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('admin.lifegroups.index', [
            'groups' => $groups,
            'categories' => LifeGroupCategory::options(),
            'campuses' => Campus::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LifeGroup::class);

        return view('admin.lifegroups.form', $this->formData($request, new LifeGroup(['status' => 'active', 'accepting_members' => true, 'is_public' => true])));
    }

    public function store(LifeGroupRequest $request, MediaService $media, LifeGroupService $service): RedirectResponse
    {
        $data = $request->safe()->except('cover');
        $data['cover_path'] = $request->file('cover') ? $media->storeImage($request->file('cover'), 'lifegroups')['path'] : null;

        $campusIds = $this->scope->campusIds($request->user());
        if (! $this->scope->isChurchWide($request->user())) {
            abort_unless(in_array((int) ($data['campus_id'] ?? 0), $campusIds, true), 403, 'Choose one of your campuses.');
        }

        $group = LifeGroup::create($data);
        $this->syncLeaders($group, $service);

        return redirect()->route('admin.lifegroups.show', $group)->with('status', 'LifeGroup created.');
    }

    public function show(Request $request, LifeGroup $lifeGroup, LifeGroupService $service): View
    {
        $this->authorize('view', $lifeGroup);

        $lifeGroup->load(['leader', 'coLeader', 'campus', 'parent']);
        $memberships = $lifeGroup->memberships()->with(['profile.currentStage', 'profile.currentProgram', 'profile.activeDiscipler.discipler'])->get()
            ->sortBy(fn ($m) => [$m->status !== 'active', array_search($m->role->value, LifeGroupRole::values()), $m->profile->full_name]);
        $activeIds = $memberships->where('status', 'active')->pluck('profile_id');

        return view('admin.lifegroups.show', [
            'group' => $lifeGroup,
            'summary' => $service->summary($lifeGroup),
            'memberships' => $memberships,
            'meetings' => $lifeGroup->meetings()->withCount(['attendances as present_count' => fn ($q) => $q->where('status', 'present'), 'attendances'])->limit(12)->get(),
            'requests' => $lifeGroup->joinRequests()->whereIn('status', [JoinRequestStatus::Pending->value, JoinRequestStatus::Contacted->value, JoinRequestStatus::Approved->value])->latest()->get(),
            'discipleship' => MemberProgramProgress::with(['profile', 'program.chapters', 'chapterProgress', 'discipler'])
                ->whereIn('profile_id', $activeIds)->where('status', ProgressStatus::InProgress->value)->get(),
            'pipeline' => LeadershipCandidate::with('profile')->whereIn('profile_id', $activeIds)->where('stage', '!=', LeadershipStage::ActiveLeader->value)->get(),
            'prayers' => $request->user()->can('prayer.view')
                ? $this->scope->prayerRequests(PrayerRequest::where('life_group_id', $lifeGroup->id), $request->user())->latest()->limit(10)->get()
                : collect(),
            'events' => $lifeGroup->events()->latest('starts_at')->limit(5)->get(),
            'roles' => LifeGroupRole::options(),
            'candidates' => $this->scope->profiles(Profile::query(), $request->user())->whereNotIn('id', $activeIds)->orderBy('full_name')->limit(500)->pluck('full_name', 'id'),
        ]);
    }

    public function edit(Request $request, LifeGroup $lifeGroup): View
    {
        $this->authorize('update', $lifeGroup);

        return view('admin.lifegroups.form', $this->formData($request, $lifeGroup));
    }

    public function update(LifeGroupRequest $request, LifeGroup $lifeGroup, MediaService $media, LifeGroupService $service): RedirectResponse
    {
        $data = $request->safe()->except('cover');
        $data['cover_path'] = $media->replace($lifeGroup->cover_path, $request->file('cover'), 'lifegroups');

        // LifeGroup leaders may edit their group's details, but appointing leaders is a pastoral decision.
        $user = $request->user();
        if (! $this->scope->isChurchWide($user) && $this->scope->campusIds($user) === []) {
            $data = collect($data)->except(['leader_profile_id', 'co_leader_profile_id', 'campus_id', 'parent_id', 'status'])->all();
        }

        $lifeGroup->update($data);
        $this->syncLeaders($lifeGroup, $service);

        return redirect()->route('admin.lifegroups.show', $lifeGroup)->with('status', 'LifeGroup updated.');
    }

    public function destroy(LifeGroup $lifeGroup): RedirectResponse
    {
        $this->authorize('delete', $lifeGroup);
        $lifeGroup->delete();

        return redirect()->route('admin.lifegroups.index')->with('status', 'LifeGroup archived.');
    }

    private function syncLeaders(LifeGroup $group, LifeGroupService $service): void
    {
        if ($group->leader) {
            $service->addMember($group, $group->leader, LifeGroupRole::Leader);
        }
        if ($group->coLeader) {
            $service->addMember($group, $group->coLeader, LifeGroupRole::CoLeader);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, LifeGroup $group): array
    {
        $churchWide = $this->scope->isChurchWide($request->user());

        return [
            'group' => $group,
            'categories' => LifeGroupCategory::options(),
            'days' => Weekday::options(),
            'campuses' => Campus::when(! $churchWide, fn ($q) => $q->whereIn('id', $this->scope->campusIds($request->user())))->orderBy('name')->pluck('name', 'id'),
            'leaders' => Profile::members()->orderBy('full_name')->pluck('full_name', 'id'),
            'parents' => LifeGroup::whereKeyNot($group->id)->orderBy('name')->pluck('name', 'id'),
            'canChangeLeader' => $churchWide || $this->scope->campusIds($request->user()) !== [],
        ];
    }
}
