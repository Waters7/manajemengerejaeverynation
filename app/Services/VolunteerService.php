<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerStatus;
use App\Models\Ministry;
use App\Models\MinistryMember;
use App\Models\Profile;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Notifications\TeamAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Serve With Us applications → ministry membership.
 * Accepting a volunteer never grants an application (system) role.
 */
class VolunteerService
{
    public function __construct(
        private PeopleService $people,
        private TimelineRecorder $timeline,
        private AuditLogger $audit,
        private TeamNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $ministryIds
     */
    public function submit(array $data, array $ministryIds, ?User $user = null, ?Profile $profile = null, ?int $involvementRequestId = null): VolunteerApplication
    {
        return DB::transaction(function () use ($data, $ministryIds, $user, $profile, $involvementRequestId) {
            $profile ??= $user?->profile ?? $this->people->findOrCreate([
                'full_name' => $data['name'],
                'whatsapp' => $data['whatsapp'],
                'email' => $data['email'] ?? null,
                'area' => $data['area'] ?? null,
            ]);

            $application = VolunteerApplication::create([
                'profile_id' => $profile->id,
                'involvement_request_id' => $involvementRequestId,
                'name' => $data['name'],
                'whatsapp' => WhatsApp::normalize($data['whatsapp']),
                'email' => $data['email'] ?? null,
                'area' => $data['area'] ?? null,
                'church_connection' => $data['church_connection'] ?? null,
                'skills' => $data['skills'] ?? [],
                'experience' => $data['experience'] ?? null,
                'availability' => $data['availability'] ?? [],
                'motivation' => $data['motivation'] ?? null,
                'status' => VolunteerApplicationStatus::Submitted,
            ]);
            $application->ministries()->sync($ministryIds);

            $this->timeline->record($profile, 'volunteer_applied', 'Applied to serve', $application->ministries->pluck('name')->implode(', '), $application);

            $ministries = Ministry::with('coordinator')->whereIn('id', $ministryIds)->get();
            $alert = new TeamAlert('volunteer', 'New volunteer application', "{$data['name']} would love to serve in ".$ministries->pluck('name')->implode(', ').'.', route('admin.volunteer-applications.show', $application));
            foreach ($ministries as $ministry) {
                $this->notifier->toUser($ministry->coordinator, $alert);
            }

            return $application;
        });
    }

    public function transition(VolunteerApplication $application, VolunteerApplicationStatus $status, ?string $interviewAt = null): VolunteerApplication
    {
        return DB::transaction(function () use ($application, $status, $interviewAt) {
            $application->fill([
                'status' => $status,
                'reviewed_by' => Auth::id(),
                'interview_at' => $interviewAt ?: $application->interview_at,
            ]);

            if (in_array($status, [VolunteerApplicationStatus::Accepted, VolunteerApplicationStatus::Declined], true)) {
                $application->decided_at = now();
            }
            $application->save();

            if ($status === VolunteerApplicationStatus::Orientation || $status === VolunteerApplicationStatus::Accepted) {
                $memberStatus = $status === VolunteerApplicationStatus::Accepted ? VolunteerStatus::Active : VolunteerStatus::Orientation;
                foreach ($application->ministries as $ministry) {
                    $this->addToMinistry($application->profile, $ministry, $memberStatus, $application->skills ?? [], $application->availability ?? []);
                }
            }

            if ($status === VolunteerApplicationStatus::Accepted) {
                $this->audit->log(AuditAction::Approve, $application, "Accepted volunteer application of {$application->name}");
            } elseif ($status === VolunteerApplicationStatus::Declined) {
                $this->audit->log(AuditAction::Reject, $application, "Declined volunteer application of {$application->name}");
            }

            return $application;
        });
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $availability
     */
    public function addToMinistry(Profile $profile, Ministry $ministry, VolunteerStatus $status = VolunteerStatus::Orientation, array $skills = [], array $availability = [], ?int $roleId = null): MinistryMember
    {
        $member = MinistryMember::firstOrNew(['ministry_id' => $ministry->id, 'profile_id' => $profile->id]);
        $wasActive = $member->exists && $member->status === VolunteerStatus::Active;

        $member->fill([
            'status' => $wasActive ? VolunteerStatus::Active : $status,
            'skills' => $skills ?: $member->skills,
            'availability' => $availability ?: $member->availability,
            'ministry_role_id' => $roleId ?? $member->ministry_role_id,
            'joined_at' => $member->joined_at ?? today(),
        ])->save();

        if (! $wasActive && $member->status === VolunteerStatus::Active) {
            $this->timeline->record($profile, 'ministry', "Joined {$ministry->name} ministry", null, $ministry);
        }

        return $member;
    }
}
