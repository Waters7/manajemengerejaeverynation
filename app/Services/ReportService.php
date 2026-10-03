<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\BaptismStatus;
use App\Enums\InvolvementStatus;
use App\Enums\LeadershipStage;
use App\Enums\ParticipantStatus;
use App\Enums\ProgressStatus;
use App\Enums\RegistrationStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerStatus;
use App\Models\Campus;
use App\Models\ClassBatch;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\Event;
use App\Models\InvolvementRequest;
use App\Models\LeadershipCandidate;
use App\Models\LifeGroup;
use App\Models\MemberProgramProgress;
use App\Models\Ministry;
use App\Models\Newcomer;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ministry reports. Every report returns columns + rows (and the column used for the bar view),
 * always narrowed to what the user may see.
 */
class ReportService
{
    public function __construct(private AccessScope $scope) {}

    /**
     * @return array<string, array{title: string, description: string, filters: list<string>}>
     */
    public static function catalog(): array
    {
        return [
            'member-growth' => ['title' => 'Member growth', 'description' => 'New people and new active members per month.', 'filters' => ['date', 'campus', 'lifegroup']],
            'newcomers' => ['title' => 'Newcomers', 'description' => 'Newcomers by first-visit month, source and journey step.', 'filters' => ['date', 'status']],
            'get-involved' => ['title' => 'Get Involved requests', 'description' => 'Registrations by status and interest.', 'filters' => ['date', 'campus', 'status']],
            'lifegroup-growth' => ['title' => 'LifeGroup growth', 'description' => 'Members and new joiners per LifeGroup.', 'filters' => ['date', 'campus', 'leader']],
            'lifegroup-attendance' => ['title' => 'LifeGroup attendance', 'description' => 'Meetings held and average attendance per LifeGroup.', 'filters' => ['date', 'campus', 'lifegroup', 'leader']],
            'discipleship-stage' => ['title' => 'Discipleship stage', 'description' => 'People per 4E stage and program in progress.', 'filters' => ['campus', 'lifegroup', 'stage']],
            'baptism' => ['title' => 'Water baptism', 'description' => 'Baptisms per month and baptism status per 4E stage.', 'filters' => ['date', 'campus', 'lifegroup']],
            'curriculum-completion' => ['title' => 'Curriculum completion', 'description' => 'Program starts and completions in the period.', 'filters' => ['date', 'program', 'stage']],
            'victory-weekend' => ['title' => 'Victory Weekend', 'description' => 'Participants, attendance and completion per weekend.', 'filters' => ['date']],
            'classes' => ['title' => 'Classes', 'description' => 'Class batches, participants and completion rate.', 'filters' => ['date', 'program', 'campus']],
            'leadership-pipeline' => ['title' => 'Leadership pipeline', 'description' => 'Candidates in each pipeline stage.', 'filters' => ['campus', 'lifegroup']],
            'campus-ministry' => ['title' => 'Campus Ministry', 'description' => 'Students, LifeGroups and discipleship per campus.', 'filters' => ['campus']],
            'volunteer-participation' => ['title' => 'Volunteer participation', 'description' => 'Volunteers, applications and serving per ministry.', 'filters' => ['date', 'ministry', 'status']],
            'event-attendance' => ['title' => 'Event attendance', 'description' => 'Registrations and check-ins per event.', 'filters' => ['date', 'campus']],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: list<string>, rows: Collection<int, list<mixed>>, bar: ?int}
     */
    public function run(string $report, User $user, array $filters): array
    {
        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : now()->subMonths(11)->startOfMonth();
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : now()->endOfDay();

        return match ($report) {
            'member-growth' => $this->memberGrowth($user, $filters, $from, $to),
            'newcomers' => $this->newcomers($user, $filters, $from, $to),
            'get-involved' => $this->getInvolved($user, $filters, $from, $to),
            'lifegroup-growth' => $this->lifeGroupGrowth($user, $filters, $from, $to),
            'lifegroup-attendance' => $this->lifeGroupAttendance($user, $filters, $from, $to),
            'discipleship-stage' => $this->discipleshipStage($user, $filters),
            'baptism' => $this->baptism($user, $filters, $from, $to),
            'curriculum-completion' => $this->curriculumCompletion($user, $filters, $from, $to),
            'victory-weekend' => $this->victoryWeekend($user, $from, $to),
            'classes' => $this->classes($user, $filters, $from, $to),
            'leadership-pipeline' => $this->leadershipPipeline($user, $filters),
            'campus-ministry' => $this->campusMinistry($user, $filters),
            'volunteer-participation' => $this->volunteerParticipation($user, $filters, $from, $to),
            'event-attendance' => $this->eventAttendance($user, $filters, $from, $to),
            default => abort(404),
        };
    }

    // ── Reports ────────────────────────────────────────────────────

    private function memberGrowth(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $people = $this->people($user, $filters)->get(['id', 'created_at', 'join_date']);
        $rows = $this->months($from, $to)->map(function (Carbon $month) use ($people) {
            $newPeople = $people->filter(fn ($p) => $p->created_at->isSameMonth($month))->count();
            $newMembers = $people->filter(fn ($p) => $p->join_date?->isSameMonth($month))->count();
            $total = $people->filter(fn ($p) => $p->join_date && $p->join_date->lte($month->copy()->endOfMonth()))->count();

            return [$month->translatedFormat('M Y'), $newPeople, $newMembers, $total];
        });

        return ['columns' => ['Month', 'New people', 'New active members', 'Total members (cumulative)'], 'rows' => $rows, 'bar' => 2];
    }

    private function newcomers(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $rows = Newcomer::query()
            ->whereHas('profile', fn ($q) => $this->scope->profiles($q, $user))
            ->whereBetween('first_visit_date', [$from, $to])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('journey_status', $status))
            ->get()
            ->groupBy(fn (Newcomer $n) => $n->source?->label() ?? 'Unknown')
            ->map(fn (Collection $group, string $source) => [
                $source,
                $group->count(),
                $group->filter(fn ($n) => $n->contacted_at)->count(),
                $group->filter(fn ($n) => $n->connected_at)->count(),
            ])
            ->sortByDesc(1)
            ->values();

        return ['columns' => ['Source', 'Newcomers', 'Contacted', 'Connected'], 'rows' => $rows, 'bar' => 1];
    }

    private function getInvolved(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $requests = $this->scope->involvementRequests(InvolvementRequest::query(), $user)
            ->with('interests')
            ->whereBetween('created_at', [$from, $to])
            ->when($filters['campus'] ?? null, fn ($q, $campus) => $q->where('campus_id', $campus))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->get();

        $rows = $requests->flatMap(fn (InvolvementRequest $r) => $r->interests->map(fn ($i) => [$i->name, $r->status]))
            ->groupBy(0)
            ->map(fn (Collection $group, string $interest) => [
                $interest,
                $group->count(),
                $group->filter(fn ($g) => $g[1] === InvolvementStatus::New)->count(),
                $group->filter(fn ($g) => in_array($g[1], [InvolvementStatus::Connected, InvolvementStatus::Active], true))->count(),
            ])
            ->sortByDesc(1)
            ->values();

        return ['columns' => ['Interest', 'Requests', 'Still new', 'Connected / active'], 'rows' => $rows, 'bar' => 1];
    }

    private function lifeGroupGrowth(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $rows = $this->groups($user, $filters)
            ->with('leader')
            ->withCount([
                'memberships as members' => fn ($q) => $q->where('status', 'active'),
                'memberships as joined' => fn ($q) => $q->whereBetween('joined_at', [$from, $to]),
                'memberships as moved_on' => fn ($q) => $q->whereBetween('left_at', [$from, $to]),
            ])
            ->get()
            ->map(fn (LifeGroup $g) => [$g->name, $g->leader?->full_name ?? '—', $g->members, $g->joined, $g->moved_on])
            ->sortByDesc(2)
            ->values();

        return ['columns' => ['LifeGroup', 'Leader', 'Active members', 'Joined in period', 'Moved on'], 'rows' => $rows, 'bar' => 2];
    }

    private function lifeGroupAttendance(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $rows = $this->groups($user, $filters)
            ->with(['meetings' => fn ($q) => $q->whereBetween('meeting_date', [$from, $to])->with('attendances')])
            ->get()
            ->map(function (LifeGroup $g) {
                $attendances = $g->meetings->flatMap->attendances;
                $present = $attendances->where('status', AttendanceStatus::Present)->count();

                return [
                    $g->name,
                    $g->meetings->count(),
                    $g->meetings->count() ? round($present / $g->meetings->count(), 1) : 0,
                    $attendances->count() ? (int) round($present / $attendances->count() * 100) : 0,
                    (int) $g->meetings->sum('visitor_count'),
                ];
            })
            ->sortByDesc(3)
            ->values();

        return ['columns' => ['LifeGroup', 'Meetings', 'Avg. present', 'Attendance rate %', 'Visitors'], 'rows' => $rows, 'bar' => 3];
    }

    private function discipleshipStage(User $user, array $filters): array
    {
        $people = $this->people($user, $filters)->select('profiles.id');
        $rows = DiscipleshipStage::ordered()
            ->when($filters['stage'] ?? null, fn ($q, $stage) => $q->whereKey($stage))
            ->with('activePrograms')->get()
            ->flatMap(fn (DiscipleshipStage $stage) => $stage->activePrograms->map(fn (DiscipleshipProgram $program) => [
                $stage->name,
                $program->name,
                MemberProgramProgress::where('discipleship_program_id', $program->id)->where('status', ProgressStatus::InProgress->value)->whereIn('profile_id', $people)->count(),
                MemberProgramProgress::where('discipleship_program_id', $program->id)->where('status', ProgressStatus::Completed->value)->whereIn('profile_id', $people)->count(),
                Profile::whereIn('id', $people)->where('current_program_id', $program->id)->count(),
            ]))
            ->values();

        return ['columns' => ['Stage', 'Program', 'In progress', 'Completed (all time)', 'Currently here'], 'rows' => $rows, 'bar' => 4];
    }

    private function baptism(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $people = $this->people($user, $filters)->get(['id', 'baptism_status', 'baptism_date', 'current_stage_id']);
        $baptized = $people->filter(fn (Profile $p) => $p->isBaptized());

        $monthly = $this->months($from, $to)->map(fn (Carbon $month) => [
            $month->translatedFormat('M Y'),
            'Baptized in month',
            $baptized->filter(fn (Profile $p) => $p->baptism_date?->isSameMonth($month))->count(),
        ]);

        $stages = DiscipleshipStage::ordered()->get()->map(function (DiscipleshipStage $stage) use ($people) {
            $inStage = $people->where('current_stage_id', $stage->id);

            return [$stage->name, 'Baptized / in stage', $inStage->filter(fn (Profile $p) => $p->isBaptized())->count().' / '.$inStage->count()];
        });

        $totals = collect(BaptismStatus::cases())->map(fn (BaptismStatus $status) => [
            'All people', $status->label(), $people->where('baptism_status', $status)->count(),
        ]);

        return ['columns' => ['Period / group', 'Measure', 'People'], 'rows' => $monthly->concat($totals)->concat($stages)->values(), 'bar' => 2];
    }

    private function curriculumCompletion(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $people = $this->people($user, $filters)->select('profiles.id');
        $rows = DiscipleshipProgram::with('stage')
            ->when($filters['program'] ?? null, fn ($q, $program) => $q->whereKey($program))
            ->when($filters['stage'] ?? null, fn ($q, $stage) => $q->where('discipleship_stage_id', $stage))
            ->orderBy('sequence')->get()
            ->sortBy(fn ($p) => [$p->stage?->sequence, $p->sequence])
            ->map(function (DiscipleshipProgram $program) use ($people, $from, $to) {
                $base = MemberProgramProgress::where('discipleship_program_id', $program->id)->whereIn('profile_id', $people);
                $started = (clone $base)->whereBetween('started_at', [$from, $to])->count();
                $completed = (clone $base)->whereBetween('completed_at', [$from, $to])->count();

                return [$program->stage?->name, $program->name, $started, $completed, (clone $base)->where('status', ProgressStatus::InProgress->value)->count()];
            })
            ->values();

        return ['columns' => ['Stage', 'Program', 'Started in period', 'Completed in period', 'In progress now'], 'rows' => $rows, 'bar' => 3];
    }

    private function victoryWeekend(User $user, Carbon $from, Carbon $to): array
    {
        $program = DiscipleshipProgram::where('slug', DiscipleshipProgram::VICTORY_WEEKEND_SLUG)->first();

        return $this->batchRows($user, ClassBatch::where('discipleship_program_id', $program?->id ?? 0), $from, $to);
    }

    private function classes(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        return $this->batchRows($user, ClassBatch::query()
            ->when($filters['program'] ?? null, fn ($q, $program) => $q->where('discipleship_program_id', $program))
            ->when($filters['campus'] ?? null, fn ($q, $campus) => $q->where('campus_id', $campus)), $from, $to);
    }

    private function batchRows(User $user, Builder $query, Carbon $from, Carbon $to): array
    {
        $rows = $this->scope->classBatches($query, $user)
            ->with(['program', 'participants.attendances', 'sessions'])
            ->where(fn ($q) => $q->whereBetween('start_date', [$from, $to])->orWhereNull('start_date'))
            ->orderBy('start_date')
            ->get()
            ->map(function (ClassBatch $batch) {
                $participants = $batch->participants->where('status', '!=', ParticipantStatus::Cancelled);
                $completed = $participants->where('status', ParticipantStatus::Completed)->count();
                $attendance = $participants->flatMap->attendances;

                return [
                    $batch->program->name,
                    $batch->name,
                    $batch->start_date?->format('Y-m-d') ?? '—',
                    $participants->count(),
                    $attendance->count() ? (int) round($attendance->where('status', AttendanceStatus::Present)->count() / $attendance->count() * 100) : 0,
                    $completed,
                    $participants->count() ? (int) round($completed / $participants->count() * 100) : 0,
                ];
            });

        return ['columns' => ['Program', 'Batch', 'Start', 'Participants', 'Attendance %', 'Completed', 'Completion %'], 'rows' => $rows, 'bar' => 3];
    }

    private function leadershipPipeline(User $user, array $filters): array
    {
        $people = $this->people($user, $filters)->select('profiles.id');
        $counts = LeadershipCandidate::whereIn('profile_id', $people)->get()->countBy(fn ($c) => $c->stage->value);
        $rows = collect(LeadershipStage::cases())->map(fn (LeadershipStage $stage) => [$stage->label(), $counts[$stage->value] ?? 0]);

        return ['columns' => ['Pipeline stage', 'People'], 'rows' => $rows, 'bar' => 1];
    }

    private function campusMinistry(User $user, array $filters): array
    {
        $rows = Campus::query()
            ->when(! $this->scope->isChurchWide($user), fn ($q) => $q->whereIn('id', $this->scope->campusIds($user)))
            ->when($filters['campus'] ?? null, fn ($q, $campus) => $q->whereKey($campus))
            ->withCount(['students', 'lifeGroups' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->map(fn (Campus $campus) => [
                $campus->name,
                $campus->students_count,
                $campus->life_groups_count,
                MemberProgramProgress::where('status', ProgressStatus::InProgress->value)->whereIn('profile_id', $campus->students()->select('id'))->distinct()->count('profile_id'),
                $campus->events()->where('starts_at', '>=', now()->subYear())->count(),
            ]);

        return ['columns' => ['Campus', 'Students', 'Campus LifeGroups', 'Being discipled', 'Events (12 months)'], 'rows' => $rows, 'bar' => 1];
    }

    private function volunteerParticipation(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $rows = $this->scope->ministries(Ministry::query(), $user)
            ->when($filters['ministry'] ?? null, fn ($q, $ministry) => $q->whereKey($ministry))
            ->withCount([
                'members as active' => fn ($q) => $q->where('status', VolunteerStatus::Active->value),
                'members as orientation' => fn ($q) => $q->where('status', VolunteerStatus::Orientation->value),
                'applications as applications' => fn ($q) => $q->whereBetween('volunteer_applications.created_at', [$from, $to]),
                'applications as accepted' => fn ($q) => $q->where('status', VolunteerApplicationStatus::Accepted->value)->whereBetween('volunteer_applications.created_at', [$from, $to]),
                'schedules as served' => fn ($q) => $q->whereBetween('serve_date', [$from, $to]),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Ministry $m) => [$m->name, $m->active, $m->orientation, $m->applications, $m->accepted, $m->served]);

        return ['columns' => ['Ministry', 'Active volunteers', 'In orientation', 'Applications', 'Accepted', 'Serving slots'], 'rows' => $rows, 'bar' => 1];
    }

    private function eventAttendance(User $user, array $filters, Carbon $from, Carbon $to): array
    {
        $rows = $this->scope->events(Event::query(), $user)
            ->whereBetween('starts_at', [$from, $to])
            ->when($filters['campus'] ?? null, fn ($q, $campus) => $q->where('campus_id', $campus))
            ->where('registration_enabled', true)
            ->withCount([
                'registrations as registered' => fn ($q) => $q->where('status', RegistrationStatus::Registered->value),
                'registrations as waiting' => fn ($q) => $q->where('status', RegistrationStatus::WaitingList->value),
                'registrations as attended' => fn ($q) => $q->whereNotNull('checked_in_at'),
            ])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Event $e) => [
                $e->title, $e->starts_at->format('Y-m-d'), $e->capacity ?? '∞', $e->registered, $e->waiting, $e->attended,
                $e->registered ? (int) round($e->attended / $e->registered * 100) : 0,
            ]);

        return ['columns' => ['Event', 'Date', 'Capacity', 'Registered', 'Waiting list', 'Checked in', 'Show-up %'], 'rows' => $rows, 'bar' => 5];
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function people(User $user, array $filters): Builder
    {
        return $this->scope->profiles(Profile::query(), $user)
            ->when($filters['campus'] ?? null, fn ($q, $campus) => $q->where('campus_id', $campus))
            ->when($filters['lifegroup'] ?? null, fn ($q, $group) => $q->whereHas('lifeGroupMemberships', fn ($m) => $m->where('life_group_id', $group)->where('status', 'active')));
    }

    private function groups(User $user, array $filters): Builder
    {
        return $this->scope->lifeGroups(LifeGroup::query(), $user)
            ->where('status', 'active')
            ->when($filters['campus'] ?? null, fn ($q, $campus) => $q->where('campus_id', $campus))
            ->when($filters['lifegroup'] ?? null, fn ($q, $group) => $q->whereKey($group))
            ->when($filters['leader'] ?? null, fn ($q, $leader) => $q->where('leader_profile_id', $leader))
            ->orderBy('name');
    }

    /** @return Collection<int, Carbon> */
    private function months(Carbon $from, Carbon $to): Collection
    {
        $months = collect();
        for ($month = $from->copy()->startOfMonth(); $month->lte($to) && $months->count() < 36; $month->addMonth()) {
            $months->push($month->copy());
        }

        return $months;
    }
}
