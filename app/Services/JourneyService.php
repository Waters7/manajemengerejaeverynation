<?php

namespace App\Services;

use App\Enums\BaptismStatus;
use App\Enums\NewcomerJourney;
use App\Enums\ProgramType;
use App\Enums\ProgressStatus;
use App\Models\CurriculumChapter;
use App\Models\DisciplerRelationship;
use App\Models\DiscipleshipMeeting;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\MemberChapterProgress;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The 4E discipleship journey: Engage → Establish → Equip → Empower.
 */
class JourneyService
{
    public function __construct(
        private TimelineRecorder $timeline,
        private PeopleService $people,
    ) {}

    /**
     * Start (or resume) a program for a person.
     */
    public function startProgram(Profile $profile, DiscipleshipProgram $program, ?Profile $discipler = null, ?string $startedAt = null, ?string $expectedCompletion = null): MemberProgramProgress
    {
        return DB::transaction(function () use ($profile, $program, $discipler, $startedAt, $expectedCompletion) {
            $progress = MemberProgramProgress::firstOrNew([
                'profile_id' => $profile->id,
                'discipleship_program_id' => $program->id,
            ]);

            $isNew = ! $progress->exists;
            $progress->fill([
                'discipler_profile_id' => $discipler?->id ?? $progress->discipler_profile_id,
                'status' => $progress->status === ProgressStatus::Completed ? ProgressStatus::Completed : ProgressStatus::InProgress,
                'started_at' => $progress->started_at ?? ($startedAt ?: today()),
                'expected_completion_at' => $expectedCompletion ?: $progress->expected_completion_at,
                'last_activity_at' => now(),
            ])->save();

            if ($isNew) {
                $this->timeline->record($profile, 'program_started', "Started {$program->name}", null, $progress);
            }

            if ($discipler && $discipler->id !== $profile->id) {
                $this->assignDiscipler($profile, $discipler);
            }

            if ($program->slug === DiscipleshipProgram::ONE2ONE_SLUG && $profile->newcomer) {
                $this->people->advanceNewcomer($profile, NewcomerJourney::One2one);
            } elseif ($profile->newcomer) {
                $this->people->advanceNewcomer($profile, NewcomerJourney::Discipleship);
            }

            $this->recalculate($profile);

            return $progress;
        });
    }

    /**
     * Update a chapter/lesson. Completing the final chapter completes the program.
     */
    public function updateChapter(MemberProgramProgress $progress, CurriculumChapter $chapter, ProgressStatus $status, ?string $date = null, ?string $notes = null, ?string $nextFollowUp = null): MemberChapterProgress
    {
        return DB::transaction(function () use ($progress, $chapter, $status, $date, $notes, $nextFollowUp) {
            $row = MemberChapterProgress::updateOrCreate(
                ['member_program_progress_id' => $progress->id, 'curriculum_chapter_id' => $chapter->id],
                [
                    'status' => $status,
                    'completed_on' => $status === ProgressStatus::Completed ? ($date ?: today()) : null,
                    'notes' => $notes,
                    'next_follow_up_at' => $nextFollowUp,
                    'recorded_by' => Auth::id(),
                ],
            );

            $progress->forceFill([
                'last_activity_at' => now(),
                'next_follow_up_at' => $nextFollowUp ?: $progress->next_follow_up_at,
                'status' => $progress->status === ProgressStatus::NotStarted ? ProgressStatus::InProgress : $progress->status,
                'started_at' => $progress->started_at ?? today(),
            ])->save();

            $progress->load('chapterProgress', 'program.chapters');
            $total = $progress->program->chapters->count();
            if ($total > 0 && $progress->completedUnits() >= $total && $progress->status !== ProgressStatus::Completed) {
                $this->completeProgram($progress);
            } elseif ($progress->completedUnits() < $total && $progress->status === ProgressStatus::Completed) {
                $progress->update(['status' => ProgressStatus::InProgress, 'completed_at' => null]);
                $this->recalculate($progress->profile);
            }

            return $row;
        });
    }

    public function completeProgram(MemberProgramProgress $progress, ?string $completedAt = null): MemberProgramProgress
    {
        return DB::transaction(function () use ($progress, $completedAt) {
            $progress->update([
                'status' => ProgressStatus::Completed,
                'completed_at' => $completedAt ?: today(),
                'last_activity_at' => now(),
            ]);

            $this->timeline->record($progress->profile, 'program_completed', "Completed {$progress->program->name}", null, $progress, $progress->completed_at);
            $this->recalculate($progress->profile);

            return $progress;
        });
    }

    public function setStatus(MemberProgramProgress $progress, ProgressStatus $status): MemberProgramProgress
    {
        if ($status === ProgressStatus::Completed) {
            return $this->completeProgram($progress);
        }

        $progress->update(['status' => $status, 'completed_at' => null, 'last_activity_at' => now()]);
        $this->recalculate($progress->profile);

        return $progress;
    }

    /**
     * Current stage = stage of the first program (in 4E order) that is in progress,
     * otherwise the stage after the last completed one.
     */
    public function recalculate(Profile $profile): void
    {
        $programs = $this->orderedPrograms();
        $progress = $profile->programProgress()->get()->keyBy('discipleship_program_id');

        $current = $programs->first(fn ($p) => $progress->get($p->id)?->status === ProgressStatus::InProgress);

        if (! $current) {
            $ordered = $programs->values();
            $lastCompletedIndex = null;
            foreach ($ordered as $index => $program) {
                if ($progress->get($program->id)?->status === ProgressStatus::Completed) {
                    $lastCompletedIndex = $index;
                }
            }
            $current = $lastCompletedIndex === null
                ? null
                : ($ordered->get($lastCompletedIndex + 1) ?? $ordered->get($lastCompletedIndex));
        }

        $profile->forceFill([
            'current_program_id' => $current?->id,
            'current_stage_id' => $current?->discipleship_stage_id,
        ])->saveQuietly();
    }

    /** @return Collection<int, DiscipleshipProgram> */
    public function orderedPrograms(): Collection
    {
        return DiscipleshipProgram::query()
            ->active()
            ->join('discipleship_stages', 'discipleship_stages.id', '=', 'discipleship_programs.discipleship_stage_id')
            ->where('discipleship_stages.is_active', true)
            ->orderBy('discipleship_stages.sequence')
            ->orderBy('discipleship_programs.sequence')
            ->select('discipleship_programs.*')
            ->get();
    }

    /**
     * Structured journey for display: stages → programs with each person's status and progress.
     *
     * @return Collection<int, array{stage: DiscipleshipStage, programs: Collection<int, array{program: DiscipleshipProgram, progress: ?MemberProgramProgress, done: int, total: int, status: ProgressStatus}>}>
     */
    public function journey(Profile $profile): Collection
    {
        $stages = DiscipleshipStage::ordered()->with(['activePrograms' => fn ($q) => $q->withCount('chapters')])->get();
        $progress = $profile->programProgress()->with(['chapterProgress', 'discipler'])->get()->keyBy('discipleship_program_id');

        return $stages->map(fn (DiscipleshipStage $stage) => [
            'stage' => $stage,
            'programs' => $stage->activePrograms->map(function (DiscipleshipProgram $program) use ($progress) {
                $row = $progress->get($program->id);
                $total = $program->chapters_count ?: (int) $program->total_sessions;

                return [
                    'program' => $program,
                    'progress' => $row,
                    'done' => $row?->status === ProgressStatus::Completed ? $total : ($row?->completedUnits() ?? 0),
                    'total' => $total,
                    'status' => $row?->status ?? ProgressStatus::NotStarted,
                ];
            }),
        ]);
    }

    // ── Water baptism ──────────────────────────────────────────────

    /**
     * Record a person's water baptism status. Becoming baptized is a journey milestone on the timeline.
     *
     * @param  array{baptism_status: string, baptism_date?: ?string, baptism_place?: ?string, baptism_notes?: ?string}  $data
     */
    public function recordBaptism(Profile $profile, array $data): Profile
    {
        return DB::transaction(function () use ($profile, $data) {
            $status = BaptismStatus::from($data['baptism_status']);
            $wasBaptized = $profile->isBaptized();

            $profile->update([
                'baptism_status' => $status,
                'baptism_date' => $status === BaptismStatus::NotYet ? null : ($data['baptism_date'] ?? $profile->baptism_date),
                'baptism_place' => $status === BaptismStatus::NotYet ? null : ($data['baptism_place'] ?? $profile->baptism_place),
                'baptism_notes' => $data['baptism_notes'] ?? $profile->baptism_notes,
            ]);

            if ($status === BaptismStatus::Baptized && ! $wasBaptized) {
                $this->timeline->record(
                    $profile,
                    'baptism',
                    'Water baptism',
                    $profile->baptism_place,
                    null,
                    $profile->baptism_date ?? now(),
                );
            } elseif ($status === BaptismStatus::Scheduled && $profile->wasChanged('baptism_status')) {
                $this->timeline->record($profile, 'baptism_scheduled', 'Water baptism scheduled', $profile->baptism_date?->translatedFormat('j F Y'));
            }

            return $profile;
        });
    }

    // ── Discipler relationships ────────────────────────────────────

    public function assignDiscipler(Profile $disciple, Profile $discipler, ?string $startedAt = null): DisciplerRelationship
    {
        abort_if($disciple->id === $discipler->id, 422, 'A person cannot disciple themselves.');

        return DB::transaction(function () use ($disciple, $discipler, $startedAt) {
            $existing = DisciplerRelationship::active()->where('disciple_profile_id', $disciple->id)->first();
            if ($existing && $existing->discipler_profile_id === $discipler->id) {
                return $existing;
            }
            $existing?->update(['status' => 'ended', 'ended_at' => today()]);

            $relationship = DisciplerRelationship::create([
                'discipler_profile_id' => $discipler->id,
                'disciple_profile_id' => $disciple->id,
                'status' => 'active',
                'started_at' => $startedAt ?: today(),
            ]);

            $this->timeline->record($disciple, 'discipler', "Being discipled by {$discipler->displayName()}", null, $relationship);

            return $relationship;
        });
    }

    public function endRelationship(DisciplerRelationship $relationship): void
    {
        $relationship->update(['status' => 'ended', 'ended_at' => today()]);
    }

    /**
     * @param  array{met_on: string, topic?: ?string, notes?: ?string, next_follow_up_at?: ?string}  $data
     */
    public function recordMeeting(DisciplerRelationship $relationship, array $data): DiscipleshipMeeting
    {
        $meeting = $relationship->meetings()->create($data + ['recorded_by' => Auth::id()]);

        MemberProgramProgress::where('profile_id', $relationship->disciple_profile_id)
            ->where('status', ProgressStatus::InProgress->value)
            ->update(['last_activity_at' => now(), 'next_follow_up_at' => $data['next_follow_up_at'] ?? null]);

        return $meeting;
    }

    /**
     * Discipleship family tree rooted at a person (multiplication, not ranking).
     *
     * @return array{profile: Profile, children: list<array<string, mixed>>}
     */
    public function tree(Profile $root, int $maxDepth = 6): array
    {
        $relationships = DisciplerRelationship::active()->with('disciple.currentStage')->get()->groupBy('discipler_profile_id');

        $build = function (Profile $profile, int $depth, array $visited) use (&$build, $relationships, $maxDepth): array {
            $children = [];
            if ($depth < $maxDepth) {
                foreach ($relationships->get($profile->id, collect()) as $relationship) {
                    if ($relationship->disciple && ! in_array($relationship->disciple->id, $visited, true)) {
                        $children[] = $build($relationship->disciple, $depth + 1, [...$visited, $relationship->disciple->id]);
                    }
                }
            }

            return ['profile' => $profile, 'children' => $children];
        };

        return $build($root, 0, [$root->id]);
    }

    /**
     * People per current stage — the discipleship funnel.
     *
     * @return Collection<int, array{stage: DiscipleshipStage, count: int}>
     */
    public function funnel(?callable $scope = null): Collection
    {
        return DiscipleshipStage::ordered()->get()->map(function (DiscipleshipStage $stage) use ($scope) {
            $query = Profile::query()->where('current_stage_id', $stage->id);
            if ($scope) {
                $scope($query);
            }

            return ['stage' => $stage, 'count' => $query->count()];
        });
    }

    public function isBookProgram(DiscipleshipProgram $program): bool
    {
        return $program->type === ProgramType::Book;
    }
}
