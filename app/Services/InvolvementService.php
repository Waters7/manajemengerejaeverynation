<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\ContactType;
use App\Enums\FollowUpCategory;
use App\Enums\FollowUpTaskStatus;
use App\Enums\InterestAction;
use App\Enums\InvolvementStatus;
use App\Enums\InvolvementType;
use App\Enums\NewcomerJourney;
use App\Enums\PrayerStatus;
use App\Enums\PrayerVisibility;
use App\Enums\VolunteerStatus;
use App\Models\DiscipleshipProgram;
use App\Models\FollowUp;
use App\Models\FollowUpTask;
use App\Models\InvolvementInterest;
use App\Models\InvolvementRequest;
use App\Models\LifeGroup;
use App\Models\MemberProgramProgress;
use App\Models\Ministry;
use App\Models\PrayerRequest;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\TeamAlert;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Get Involved / Connect Card workflow:
 * NEW → CONTACTED → FOLLOW-UP → CONNECTED → ACTIVE (or NOT CONTINUING).
 */
class InvolvementService
{
    public function __construct(
        private PeopleService $people,
        private TimelineRecorder $timeline,
        private AuditLogger $audit,
        private TeamNotifier $notifier,
        private LifeGroupService $lifeGroups,
        private VolunteerService $volunteers,
        private JourneyService $journey,
        private WhatsApp $whatsApp,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated form data
     */
    public function submit(array $data, InvolvementType $type, ?User $user = null, ?string $ip = null): InvolvementRequest
    {
        return DB::transaction(function () use ($data, $type, $user, $ip) {
            $personal = [
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'whatsapp' => $data['whatsapp'],
                'email' => $data['email'] ?? null,
                'area' => $data['area'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'company' => $data['company'] ?? null,
                'school_name' => $data['campus_name'] ?? null,
                'campus_id' => $data['campus_id'] ?? null,
                'life_stage' => $data['life_stage'] ?? null,
                'source' => $data['source'] ?? null,
            ];

            $profile = $user?->profile ?? $this->people->findOrCreate($personal);
            $this->people->advanceNewcomer($profile, NewcomerJourney::ConnectCard);

            $request = InvolvementRequest::create([
                ...collect($personal)->except(['school_name'])->all(),
                'whatsapp' => WhatsApp::normalize($data['whatsapp']),
                'campus_name' => $data['campus_name'] ?? null,
                'profile_id' => $profile->id,
                'type' => $type,
                'source_other' => $data['source_other'] ?? null,
                'experience' => $data['experience'] ?? null,
                'skills' => $data['skills'] ?? [],
                'availability' => $data['availability'] ?? [],
                'motivation' => $data['motivation'] ?? null,
                'status' => InvolvementStatus::New,
                'submitted_ip' => $ip,
            ]);

            $interestIds = array_map('intval', $data['interests'] ?? []);
            $request->interests()->sync($interestIds);
            $ministryIds = array_map('intval', $data['ministries'] ?? []);
            $request->ministries()->sync($ministryIds);
            $request->load('interests', 'ministries');

            $this->timeline->record(
                $profile,
                $type === InvolvementType::ConnectCard ? 'connect_card' : 'get_involved',
                $type === InvolvementType::ConnectCard ? 'Filled a Connect Card' : 'Registered through Get Involved',
                $request->interests->pluck('name')->implode(', ') ?: null,
                $request,
            );

            $this->createSideRecords($request, $profile, $data);

            $this->notifier->toPermission('involvement.manage', new TeamAlert(
                'involvement',
                $type === InvolvementType::ConnectCard ? 'New Connect Card' : 'New Get Involved registration',
                "{$request->full_name} just connected".($request->interests->isNotEmpty() ? ' — interested in '.$request->interests->pluck('name')->implode(', ') : '').'.',
                route('admin.involvement.show', $request),
            ));

            return $request;
        });
    }

    /**
     * Prayer request, pastoral follow-up task and volunteer application derived from the chosen interests.
     *
     * @param  array<string, mixed>  $data
     */
    private function createSideRecords(InvolvementRequest $request, Profile $profile, array $data): void
    {
        if ($request->hasInterest(InterestAction::Prayer) && filled($data['prayer_request'] ?? null)) {
            PrayerRequest::create([
                'profile_id' => $profile->id,
                'name' => $request->full_name,
                'contact' => $request->whatsapp,
                'request' => $data['prayer_request'],
                'visibility' => PrayerVisibility::PastorOnly,
                'status' => PrayerStatus::New,
                'source' => $request->type->value,
            ]);
        }

        if ($request->hasInterest(InterestAction::Pastoral)) {
            FollowUpTask::create([
                'profile_id' => $profile->id,
                'subject_type' => $request->getMorphClass(),
                'subject_id' => $request->id,
                'title' => "Pastoral follow-up requested by {$request->full_name}",
                'category' => FollowUpCategory::Care,
                'status' => FollowUpTaskStatus::Open,
                'due_date' => today()->addDays(2),
            ]);
        }

        $wantsToServe = $request->hasInterest(InterestAction::Volunteer) || $request->hasInterest(InterestAction::Ministry);
        if ($wantsToServe && $request->ministries->isNotEmpty()) {
            $this->volunteers->submit([
                'name' => $request->full_name,
                'whatsapp' => $request->whatsapp,
                'email' => $request->email,
                'area' => $request->area,
                'church_connection' => 'Get Involved form',
                'skills' => $request->skills ?? [],
                'experience' => $request->experience,
                'availability' => $request->availability ?? [],
                'motivation' => $request->motivation,
            ], $request->ministries->pluck('id')->all(), null, $profile, $request->id);
        }
    }

    public function assign(InvolvementRequest $request, User $assignee, ?string $dueDate = null): FollowUpTask
    {
        return DB::transaction(function () use ($request, $assignee, $dueDate) {
            $request->update([
                'assigned_to' => $assignee->id,
                'assigned_at' => now(),
                'due_date' => $dueDate,
                'status' => $request->status === InvolvementStatus::New ? InvolvementStatus::FollowUp : $request->status,
            ]);

            $request->profile?->newcomer?->update(['assigned_to' => $assignee->id]);

            FollowUpTask::where('subject_type', $request->getMorphClass())
                ->where('subject_id', $request->id)
                ->where('category', FollowUpCategory::Involvement->value)
                ->pending()
                ->update(['status' => FollowUpTaskStatus::Cancelled->value]);

            $task = FollowUpTask::create([
                'profile_id' => $request->profile_id,
                'subject_type' => $request->getMorphClass(),
                'subject_id' => $request->id,
                'title' => "Follow up {$request->displayName()}".($request->interests->isNotEmpty() ? ' — '.$request->interests->pluck('name')->implode(', ') : ''),
                'category' => FollowUpCategory::Involvement,
                'assigned_to' => $assignee->id,
                'assigned_by' => Auth::id(),
                'due_date' => $dueDate,
                'status' => FollowUpTaskStatus::Open,
            ]);

            $this->audit->log(AuditAction::Assign, $request, "Assigned {$request->full_name} to {$assignee->name} for follow-up");
            $this->notifier->toUser($assignee, new TeamAlert(
                'follow_up',
                'New follow-up assigned to you',
                "Please connect with {$request->full_name}".($dueDate ? ' by '.Carbon::parse($dueDate)->translatedFormat('j F Y') : '').'.',
                route('admin.involvement.show', $request),
                sendMail: true,
            ));

            return $task;
        });
    }

    public function updateStatus(InvolvementRequest $request, InvolvementStatus $status): InvolvementRequest
    {
        return DB::transaction(function () use ($request, $status) {
            $request->status = $status;
            if (in_array($status, [InvolvementStatus::Contacted, InvolvementStatus::FollowUp], true)) {
                $request->contacted_at ??= now();
            }
            $request->save();

            $profile = $request->profile;
            if ($profile) {
                match ($status) {
                    InvolvementStatus::Contacted, InvolvementStatus::FollowUp => $this->people->advanceNewcomer($profile, NewcomerJourney::Contacted),
                    InvolvementStatus::Connected => $this->people->advanceNewcomer($profile, NewcomerJourney::Connected),
                    InvolvementStatus::Active => $this->people->activateMember($profile),
                    default => null,
                };
            }

            if (in_array($status, [InvolvementStatus::Connected, InvolvementStatus::Active, InvolvementStatus::NotContinuing], true)) {
                FollowUpTask::where('subject_type', $request->getMorphClass())
                    ->where('subject_id', $request->id)
                    ->pending()
                    ->update(['status' => FollowUpTaskStatus::Done->value, 'completed_at' => now()]);
            }

            return $request;
        });
    }

    public function addNote(InvolvementRequest $request, string $body, ContactType $type = ContactType::Note): FollowUp
    {
        $note = $request->notes()->create([
            'profile_id' => $request->profile_id,
            'user_id' => Auth::id(),
            'type' => $type,
            'body' => $body,
            'is_internal' => true,
        ]);

        if ($type !== ContactType::Note && $request->status === InvolvementStatus::New) {
            $this->updateStatus($request, InvolvementStatus::Contacted);
        }

        return $note;
    }

    public function assignLifeGroup(InvolvementRequest $request, LifeGroup $group): void
    {
        $this->lifeGroups->addMember($group, $request->profile);
        $this->updateStatus($request, InvolvementStatus::Connected);
        $this->audit->log(AuditAction::Assign, $request, "{$request->full_name} added to LifeGroup {$group->name}");
    }

    public function assignMinistry(InvolvementRequest $request, Ministry $ministry): void
    {
        $this->volunteers->addToMinistry($request->profile, $ministry, VolunteerStatus::Orientation, $request->skills ?? [], $request->availability ?? []);
        $this->audit->log(AuditAction::Assign, $request, "{$request->full_name} introduced to {$ministry->name} ministry");
    }

    public function startOne2One(InvolvementRequest $request, ?Profile $discipler = null): MemberProgramProgress
    {
        $program = DiscipleshipProgram::one2one();
        abort_unless($program, 422, 'The One 2 One program is not configured in the curriculum.');

        $progress = $this->journey->startProgram($request->profile, $program, $discipler);
        if (in_array($request->status, [InvolvementStatus::New, InvolvementStatus::Contacted, InvolvementStatus::FollowUp], true)) {
            $this->updateStatus($request, InvolvementStatus::Connected);
        }

        return $progress;
    }

    public function activateMember(InvolvementRequest $request): void
    {
        $this->updateStatus($request, InvolvementStatus::Active);
    }

    public function archive(InvolvementRequest $request): void
    {
        $request->update(['archived_at' => now()]);
    }

    public function restore(InvolvementRequest $request): void
    {
        $request->update(['archived_at' => null]);
    }

    public function whatsappLink(InvolvementRequest $request, User $sender): ?string
    {
        return $this->whatsApp->templateLink($request->whatsapp, 'wa_template_followup', [
            'nickname' => $request->displayName(),
            'name' => $request->full_name,
            'followup_person' => $sender->displayName(),
            'interest' => $request->interests->pluck('name')->map(fn ($n) => mb_strtolower($n))->implode(', ') ?: 'Every Nation Bekasi',
        ]);
    }

    /** @return Collection<int, InvolvementInterest> */
    public function interests(bool $connectCardOnly = false)
    {
        return InvolvementInterest::active()->when($connectCardOnly, fn ($q) => $q->where('on_connect_card', true))->get();
    }
}
