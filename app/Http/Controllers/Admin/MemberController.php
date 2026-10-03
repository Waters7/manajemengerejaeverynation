<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ContactType;
use App\Enums\DiscoverySource;
use App\Enums\FollowUpCategory;
use App\Enums\Gender;
use App\Enums\LifeGroupRole;
use App\Enums\LifeStage;
use App\Enums\MemberStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use App\Models\Campus;
use App\Models\DiscipleshipStage;
use App\Models\LifeGroup;
use App\Models\LifeGroupAttendance;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\JourneyService;
use App\Services\LifeGroupService;
use App\Services\MediaService;
use App\Services\PeopleService;
use App\Services\TimelineRecorder;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Profile::class);
        $user = $request->user();

        $people = $this->scope->profiles(Profile::query(), $user)
            ->with(['activeLifeGroups', 'currentStage', 'campus'])
            ->search($request->string('q')->value())
            ->when($request->filled('status'), fn ($q) => $q->where('member_status', $request->string('status')))
            ->when($request->filled('stage'), fn ($q) => $q->where('current_stage_id', $request->integer('stage')))
            ->when($request->filled('campus'), fn ($q) => $q->where('campus_id', $request->integer('campus')))
            ->when($request->filled('lifegroup'), fn ($q) => $q->whereHas('lifeGroupMemberships', fn ($m) => $m->where('life_group_id', $request->integer('lifegroup'))->where('status', 'active')))
            ->when($request->input('lifegroup') === 'none', fn ($q) => $q->whereDoesntHave('lifeGroupMemberships', fn ($m) => $m->where('status', 'active')))
            ->when($request->input('needs') === 'discipler', fn ($q) => $q->whereIn('member_status', [MemberStatus::Connected->value, MemberStatus::Member->value])
                ->whereDoesntHave('disciplerRelationships', fn ($r) => $r->where('status', 'active')))
            ->orderBy($request->input('sort') === 'recent' ? 'created_at' : 'full_name', $request->input('sort') === 'recent' ? 'desc' : 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.members.index', [
            'people' => $people,
            'statuses' => MemberStatus::options(),
            'stages' => DiscipleshipStage::ordered()->pluck('name', 'id'),
            'campuses' => Campus::orderBy('name')->pluck('name', 'id'),
            'lifeGroups' => $this->scope->lifeGroups(LifeGroup::active(), $user)->orderBy('name')->pluck('name', 'id')->prepend('— Not in a LifeGroup —', 'none'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Profile::class);

        return view('admin.members.form', $this->formData(new Profile(['member_status' => MemberStatus::Member])));
    }

    public function store(ProfileRequest $request, MediaService $media, TimelineRecorder $timeline): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['whatsapp'] = WhatsApp::normalize($data['whatsapp'] ?? null);
        $data['photo_path'] = $request->file('photo') ? $media->storeImage($request->file('photo'), 'profiles', 800)['path'] : null;
        $data['created_by'] = $request->user()->id;

        $profile = Profile::create($data);
        $timeline->record($profile, 'registered', 'Added to the church community', null, null, $profile->first_visit_date ?? now());

        return redirect()->route('admin.members.show', $profile)->with('status', "{$profile->full_name} has been added.");
    }

    public function show(Request $request, Profile $profile, JourneyService $journey, WhatsApp $whatsApp): View
    {
        $this->authorize('view', $profile);

        $profile->load([
            'user.roles', 'campus', 'newcomer.assignee', 'currentStage', 'currentProgram',
            'lifeGroupMemberships.lifeGroup', 'activeDiscipler.discipler', 'leadershipCandidate',
            'ministryMemberships.ministry', 'ministryMemberships.role',
            'classParticipations.batch.program', 'classParticipations.attendances',
            'eventRegistrations.event',
        ]);

        $groupIds = $profile->lifeGroupMemberships->pluck('life_group_id');

        return view('admin.members.show', [
            'profile' => $profile,
            'stages' => $journey->journey($profile),
            'activeProgress' => $profile->programProgress()->with('program')->where('status', 'in_progress')->get(),
            'programs' => $journey->orderedPrograms(),
            'disciples' => $profile->discipleRelationships()->active()->with('disciple.currentStage')->get(),
            'meetings' => $profile->activeDiscipler?->meetings()->limit(10)->get() ?? collect(),
            'attendance' => LifeGroupAttendance::with('meeting.lifeGroup')
                ->where('profile_id', $profile->id)->latest('id')->limit(20)->get(),
            'notes' => $request->user()->can('viewInternalNotes', $profile)
                ? $profile->followUps()->with('author')->latest()->limit(30)->get() : collect(),
            'tasks' => $profile->followUpTasks()->with('assignee')->latest()->limit(10)->get(),
            'timeline' => $profile->timeline()->limit(60)->get(),
            'availableGroups' => $this->scope->lifeGroups(LifeGroup::active(), $request->user())->whereNotIn('id', $groupIds)->orderBy('name')->pluck('name', 'id'),
            'disciplerOptions' => $this->scope->profiles(Profile::members(), $request->user())->whereKeyNot($profile->id)->orderBy('full_name')->pluck('full_name', 'id'),
            'teamMembers' => User::permission('followups.manage')->where('account_status', AccountStatus::Active->value)->orderBy('name')->pluck('name', 'id'),
            'waLink' => $whatsApp->link($profile->whatsapp, 'Hi '.$profile->displayName().'! 👋'),
            'roles' => LifeGroupRole::options(),
        ]);
    }

    public function edit(Profile $profile): View
    {
        $this->authorize('update', $profile);

        return view('admin.members.form', $this->formData($profile));
    }

    public function update(ProfileRequest $request, Profile $profile, MediaService $media): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['whatsapp'] = WhatsApp::normalize($data['whatsapp'] ?? null);
        $data['photo_path'] = $media->replace($profile->photo_path, $request->file('photo'), 'profiles', 800);

        $profile->update($data);

        // Keep the login account in sync with the person record.
        $profile->user?->update(array_filter([
            'name' => $data['full_name'],
            'nickname' => $data['nickname'] ?? null,
            'whatsapp' => $data['whatsapp'],
        ]));

        return redirect()->route('admin.members.show', $profile)->with('status', 'Profile updated.');
    }

    public function destroy(Profile $profile): RedirectResponse
    {
        $this->authorize('delete', $profile);
        $profile->delete();

        return redirect()->route('admin.members.index')->with('status', "{$profile->full_name} has been archived.");
    }

    public function note(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('view', $profile);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
            'type' => ['required', Rule::enum(ContactType::class)],
        ]);

        $profile->followUps()->create($data + ['user_id' => $request->user()->id, 'is_internal' => true]);

        return back()->with('status', 'Note added.');
    }

    public function activate(Profile $profile, PeopleService $people): RedirectResponse
    {
        $this->authorize('update', $profile);
        $people->activateMember($profile);

        return back()->with('status', "{$profile->displayName()} is now an active member.");
    }

    public function assignLifeGroup(Request $request, Profile $profile, LifeGroupService $lifeGroups): RedirectResponse
    {
        $this->authorize('update', $profile);
        $data = $request->validate([
            'life_group_id' => ['required', Rule::exists('life_groups', 'id')],
            'role' => ['required', Rule::enum(LifeGroupRole::class)],
        ]);

        $group = LifeGroup::findOrFail($data['life_group_id']);
        abort_unless($this->scope->canManageLifeGroup($request->user(), $group), 403);

        $lifeGroups->addMember($group, $profile, LifeGroupRole::from($data['role']));

        return back()->with('status', "Added to {$group->name}.");
    }

    /** Create a login account (role USER) for a person and e-mail them a link to set their password. */
    public function createAccount(Profile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);
        abort_if($profile->user_id, 422, 'This person already has an account.');
        abort_unless(filled($profile->email), 422, 'An e-mail address is required to create an account.');
        abort_if(User::where('email', $profile->email)->exists(), 422, 'Another account already uses this e-mail address.');

        DB::transaction(function () use ($profile) {
            $user = User::create([
                'name' => $profile->full_name,
                'nickname' => $profile->nickname,
                'email' => $profile->email,
                'whatsapp' => $profile->whatsapp,
                'password' => Str::password(24),
                'account_status' => AccountStatus::Active,
            ]);
            $user->assignRole(Role::User->value);
            $profile->forceFill(['user_id' => $user->id])->save();
        });

        Password::sendResetLink(['email' => $profile->email]);

        return back()->with('status', 'Account created. A link to set the password was sent to '.$profile->email.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Profile $profile): array
    {
        return [
            'profile' => $profile,
            'genders' => Gender::options(),
            'lifeStages' => LifeStage::options(),
            'sources' => DiscoverySource::options(),
            'statuses' => MemberStatus::options(),
            'campuses' => Campus::orderBy('name')->pluck('name', 'id'),
            'categories' => FollowUpCategory::options(),
        ];
    }
}
