<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\AuditAction;
use App\Enums\JoinRequestStatus;
use App\Enums\LeadershipStage;
use App\Enums\LifeGroupRole;
use App\Enums\MemberStatus;
use App\Enums\NewcomerJourney;
use App\Enums\ProgressStatus;
use App\Models\LifeGroup;
use App\Models\LifeGroupAttendance;
use App\Models\LifeGroupJoinRequest;
use App\Models\LifeGroupMeeting;
use App\Models\LifeGroupMember;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\TeamAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LifeGroupService
{
    public function __construct(
        private PeopleService $people,
        private TimelineRecorder $timeline,
        private AuditLogger $audit,
        private TeamNotifier $notifier,
    ) {}

    /**
     * Public "JOIN THIS LIFEGROUP" form.
     *
     * @param  array{name: string, whatsapp: string, email?: ?string, area?: ?string, age?: ?int, notes?: ?string}  $data
     */
    public function submitJoinRequest(LifeGroup $group, array $data, ?User $user = null): LifeGroupJoinRequest
    {
        return DB::transaction(function () use ($group, $data, $user) {
            $profile = $user?->profile ?? $this->people->findOrCreate([
                'full_name' => $data['name'],
                'whatsapp' => $data['whatsapp'],
                'email' => $data['email'] ?? null,
                'area' => $data['area'] ?? null,
                'source' => 'lifegroup',
            ]);

            $request = $group->joinRequests()->create([
                'profile_id' => $profile->id,
                'name' => $data['name'],
                'whatsapp' => WhatsApp::normalize($data['whatsapp']),
                'email' => $data['email'] ?? null,
                'area' => $data['area'] ?? null,
                'age' => $data['age'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => JoinRequestStatus::Pending,
            ]);

            $this->timeline->record($profile, 'lifegroup_request', "Requested to join LifeGroup {$group->name}", null, $request);

            $alert = new TeamAlert('lifegroup', 'New LifeGroup join request', "{$data['name']} would like to join {$group->name}.", route('admin.join-requests.index'));
            foreach ($group->leaderProfileIds() as $leaderProfileId) {
                $this->notifier->toUser(Profile::find($leaderProfileId)?->user, $alert);
            }

            return $request;
        });
    }

    public function updateJoinRequestStatus(LifeGroupJoinRequest $request, JoinRequestStatus $status): LifeGroupJoinRequest
    {
        return DB::transaction(function () use ($request, $status) {
            $request->status = $status;
            $request->handled_by = Auth::id();

            match ($status) {
                JoinRequestStatus::Contacted => $request->contacted_at ??= now(),
                JoinRequestStatus::Approved => $request->approved_at ??= now(),
                JoinRequestStatus::Joined => $request->joined_at ??= now(),
                default => null,
            };
            $request->save();

            if ($status === JoinRequestStatus::Approved) {
                $this->audit->log(AuditAction::Approve, $request, "Approved LifeGroup join request of {$request->name}");
            } elseif ($status === JoinRequestStatus::Rejected) {
                $this->audit->log(AuditAction::Reject, $request, "Closed LifeGroup join request of {$request->name}");
            }

            if ($status === JoinRequestStatus::Joined && $request->profile) {
                $this->addMember($request->lifeGroup, $request->profile);
            }

            return $request;
        });
    }

    public function markInviteShared(LifeGroupJoinRequest $request): void
    {
        $request->forceFill(['invite_shared_at' => now()])->save();
    }

    public function addMember(LifeGroup $group, Profile $profile, LifeGroupRole $role = LifeGroupRole::Member): LifeGroupMember
    {
        return DB::transaction(function () use ($group, $profile, $role) {
            $membership = LifeGroupMember::firstOrNew(['life_group_id' => $group->id, 'profile_id' => $profile->id]);
            $isNew = ! $membership->exists || $membership->status !== 'active';

            $membership->fill([
                'role' => $role,
                'status' => 'active',
                'joined_at' => $membership->joined_at ?? today(),
                'left_at' => null,
            ])->save();

            if ($isNew) {
                $this->timeline->record($profile, 'lifegroup', "Joined LifeGroup {$group->name}", null, $group);
                if (in_array($profile->member_status, [MemberStatus::Visitor, MemberStatus::Newcomer], true)) {
                    $profile->update(['member_status' => MemberStatus::Connected]);
                }
                if ($profile->newcomer) {
                    $this->people->advanceNewcomer($profile, NewcomerJourney::Lifegroup);
                }
            }

            return $membership;
        });
    }

    public function removeMember(LifeGroupMember $membership): void
    {
        $membership->update(['status' => 'inactive', 'left_at' => today()]);
        $this->timeline->record($membership->profile, 'lifegroup_left', "Moved on from LifeGroup {$membership->lifeGroup->name}");
    }

    /**
     * Record a meeting and its attendance in one go.
     *
     * @param  array{meeting_date: string, topic?: ?string, location?: ?string, notes?: ?string, visitor_count?: ?int, leader_profile_id?: ?int}  $data
     * @param  array<int, string>  $attendance  profile_id => present|absent|excused
     */
    public function recordMeeting(LifeGroup $group, array $data, array $attendance, ?LifeGroupMeeting $meeting = null): LifeGroupMeeting
    {
        return DB::transaction(function () use ($group, $data, $attendance, $meeting) {
            $meeting ??= new LifeGroupMeeting(['life_group_id' => $group->id, 'recorded_by' => Auth::id()]);
            $meeting->fill($data + ['leader_profile_id' => $group->leader_profile_id])->save();

            $memberIds = $group->memberships()->pluck('profile_id')->all();
            foreach ($attendance as $profileId => $status) {
                if (! in_array((int) $profileId, $memberIds, true) || ! AttendanceStatus::tryFrom($status)) {
                    continue;
                }
                LifeGroupAttendance::updateOrCreate(
                    ['life_group_meeting_id' => $meeting->id, 'profile_id' => $profileId],
                    ['status' => $status],
                );
            }

            return $meeting->load('attendances');
        });
    }

    /**
     * Summary numbers for the LifeGroup dashboard.
     *
     * @return array{members: int, visitors: int, being_discipled: int, disciplers: int, potential_leaders: int, attendance_rate: ?int}
     */
    public function summary(LifeGroup $group): array
    {
        $memberIds = $group->memberships()->where('status', 'active')->pluck('profile_id');
        $visitors = $group->memberships()->where('status', 'active')->where('role', LifeGroupRole::Visitor->value)->count();

        $recentMeetingIds = $group->meetings()->limit(8)->pluck('id');
        $attendances = LifeGroupAttendance::whereIn('life_group_meeting_id', $recentMeetingIds)->get();
        $rate = $attendances->count() > 0
            ? (int) round($attendances->where('status', AttendanceStatus::Present)->count() / $attendances->count() * 100)
            : null;

        return [
            'members' => $memberIds->count() - $visitors,
            'visitors' => $visitors,
            'being_discipled' => DB::table('member_program_progress')->whereIn('profile_id', $memberIds)->where('status', ProgressStatus::InProgress->value)->distinct()->count('profile_id'),
            'disciplers' => DB::table('discipler_relationships')->whereIn('discipler_profile_id', $memberIds)->where('status', 'active')->distinct()->count('discipler_profile_id'),
            'potential_leaders' => DB::table('leadership_candidates')->whereIn('profile_id', $memberIds)->whereNotIn('stage', [LeadershipStage::ActiveLeader->value])->count(),
            'attendance_rate' => $rate,
        ];
    }
}
