<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\BatchStatus;
use App\Enums\InvolvementStatus;
use App\Enums\JoinRequestStatus;
use App\Enums\MemberStatus;
use App\Enums\ParticipantStatus;
use App\Enums\ProgressStatus;
use App\Models\ClassParticipant;
use App\Models\DiscipleshipProgram;
use App\Models\FollowUpTask;
use App\Models\InvolvementRequest;
use App\Models\LifeGroupJoinRequest;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "Needs Follow-Up" — finds people who would benefit from a caring touch.
 * Wording is intentionally pastoral; people are never labelled negatively.
 */
class CareRadar
{
    public function __construct(private AccessScope $scope) {}

    /**
     * @return Collection<string, array{key: string, label: string, hint: string, count: int, items: Collection<int, mixed>, url: string}>
     */
    public function sections(User $user, int $limit = 5): Collection
    {
        $sections = collect();

        $add = function (string $key, string $label, string $hint, $query, string $url, callable $present) use ($sections, $limit) {
            $count = (clone $query)->count();
            if ($count > 0) {
                $sections->put($key, [
                    'key' => $key,
                    'label' => $label,
                    'hint' => $hint,
                    'count' => $count,
                    'items' => $query->limit($limit)->get()->map($present),
                    'url' => $url,
                ]);
            }
        };

        $add('my_tasks', 'My follow-ups', 'Assigned to you and still open',
            FollowUpTask::with('profile')->pending()->where('assigned_to', $user->id)->orderByRaw('due_date is null')->orderBy('due_date'),
            route('admin.follow-ups.index', ['mine' => 1]),
            fn (FollowUpTask $t) => ['title' => $t->title, 'meta' => $t->due_date ? 'Due '.$t->due_date->translatedFormat('j M') : 'No due date', 'urgent' => $t->isOverdue(), 'url' => route('admin.follow-ups.index', ['mine' => 1])]);

        if ($user->can('involvement.view')) {
            $add('not_contacted', 'Waiting for a first hello', 'New connections not yet contacted',
                $this->scope->involvementRequests(InvolvementRequest::query()->open()->where('status', InvolvementStatus::New->value), $user)->oldest(),
                route('admin.involvement.index', ['status' => 'new']),
                fn (InvolvementRequest $r) => ['title' => $r->full_name, 'meta' => 'Connected '.$r->created_at->diffForHumans(), 'urgent' => $r->created_at->lt(now()->subDays(3)), 'url' => route('admin.involvement.show', $r)]);
        }

        if ($user->can('lifegroups.requests')) {
            $add('lifegroup_requests', 'Hoping to join a LifeGroup', 'Join requests not yet followed up',
                $this->scope->joinRequests(LifeGroupJoinRequest::with('lifeGroup')->where('status', JoinRequestStatus::Pending->value), $user)->oldest(),
                route('admin.join-requests.index', ['status' => 'pending']),
                fn (LifeGroupJoinRequest $r) => ['title' => $r->name, 'meta' => $r->lifeGroup?->name.' · '.$r->created_at->diffForHumans(), 'urgent' => $r->created_at->lt(now()->subDays(3)), 'url' => route('admin.join-requests.index', ['status' => 'pending'])]);
        }

        if ($user->can('discipleship.view')) {
            $add('no_discipler', 'Ready for a discipler', 'Connected people without a discipler yet',
                $this->scope->profiles(Profile::query()
                    ->whereIn('member_status', [MemberStatus::Connected->value, MemberStatus::Member->value])
                    ->whereDoesntHave('disciplerRelationships', fn ($q) => $q->where('status', 'active')), $user)->latest(),
                route('admin.members.index', ['needs' => 'discipler']),
                fn (Profile $p) => ['title' => $p->full_name, 'meta' => $p->member_status->label(), 'urgent' => false, 'url' => route('admin.members.show', $p)]);

            $one2oneId = DiscipleshipProgram::where('slug', DiscipleshipProgram::ONE2ONE_SLUG)->value('id');
            $add('one2one_quiet', 'One 2 One to reconnect', 'No One 2 One activity for 14+ days',
                $this->quietProgress($user, $one2oneId, 14, true),
                route('admin.one2one.index', ['quiet' => 1]),
                fn (MemberProgramProgress $p) => ['title' => $p->profile->full_name, 'meta' => 'Last activity '.($p->last_activity_at?->diffForHumans() ?? '—'), 'urgent' => false, 'url' => route('admin.members.show', $p->profile_id)]);

            $add('journey_quiet', 'Journeys to encourage', 'No discipleship activity for 30+ days',
                $this->quietProgress($user, $one2oneId, 30, false),
                route('admin.journey.index', ['quiet' => 1]),
                fn (MemberProgramProgress $p) => ['title' => $p->profile->full_name, 'meta' => $p->program->name.' · '.($p->last_activity_at?->diffForHumans() ?? '—'), 'urgent' => false, 'url' => route('admin.members.show', $p->profile_id)]);
        }

        if ($user->can('classes.view')) {
            $add('class_absence', 'Missed us in class', 'Absent from 2+ sessions in an ongoing class',
                ClassParticipant::with(['profile', 'batch.program'])
                    ->whereHas('batch', fn ($q) => $this->scope->classBatches($q->where('status', BatchStatus::Ongoing->value), $user))
                    ->whereNotIn('status', [ParticipantStatus::Cancelled->value, ParticipantStatus::Completed->value])
                    ->whereHas('attendances', fn ($q) => $q->where('status', AttendanceStatus::Absent->value), '>=', 2),
                route('admin.classes.index'),
                fn (ClassParticipant $p) => ['title' => $p->profile->full_name, 'meta' => $p->batch->fullLabel(), 'urgent' => false, 'url' => route('admin.classes.show', $p->class_batch_id)]);
        }

        if ($user->can('volunteers.manage')) {
            $add('volunteers_waiting', 'Volunteers waiting to hear back', 'Applications awaiting a response',
                $this->scope->volunteerApplications(VolunteerApplication::query()->awaiting(), $user)->oldest(),
                route('admin.volunteer-applications.index'),
                fn (VolunteerApplication $a) => ['title' => $a->name, 'meta' => $a->status->label().' · '.$a->created_at->diffForHumans(), 'urgent' => $a->created_at->lt(now()->subDays(7)), 'url' => route('admin.volunteer-applications.show', $a)]);
        }

        return $sections;
    }

    public function total(User $user): int
    {
        return $this->sections($user, 0)->sum('count');
    }

    private function quietProgress(User $user, ?int $one2oneId, int $days, bool $one2one)
    {
        return MemberProgramProgress::with(['profile', 'program'])
            ->where('status', ProgressStatus::InProgress->value)
            ->when($one2one, fn ($q) => $q->where('discipleship_program_id', $one2oneId ?? 0), fn ($q) => $q->where('discipleship_program_id', '!=', $one2oneId ?? 0))
            ->where(fn ($q) => $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', Carbon::now()->subDays($days)))
            ->whereHas('profile', fn ($q) => $this->scope->profiles($q, $user))
            ->orderBy('last_activity_at');
    }
}
