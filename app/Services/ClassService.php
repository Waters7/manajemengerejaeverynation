<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipantStatus;
use App\Enums\ProgressStatus;
use App\Models\ClassAttendance;
use App\Models\ClassBatch;
use App\Models\ClassParticipant;
use App\Models\ClassSession;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Class batches (Preparing for Victory, Church Community, Leadership 113…), sessions,
 * attendance and completion. Victory Weekend uses the same engine.
 */
class ClassService
{
    public function __construct(
        private JourneyService $journey,
        private TimelineRecorder $timeline,
    ) {}

    public function enroll(ClassBatch $batch, Profile $profile, bool $enforceCapacity = true): ClassParticipant
    {
        return DB::transaction(function () use ($batch, $profile, $enforceCapacity) {
            $existing = ClassParticipant::where('class_batch_id', $batch->id)->where('profile_id', $profile->id)->first();
            if ($existing && $existing->status !== ParticipantStatus::Cancelled) {
                return $existing;
            }

            if ($enforceCapacity && $batch->isFull()) {
                throw ValidationException::withMessages(['profile_id' => 'This class batch is already full.']);
            }

            $participant = $existing ?? new ClassParticipant(['class_batch_id' => $batch->id, 'profile_id' => $profile->id]);
            $participant->fill(['status' => ParticipantStatus::Registered, 'registered_at' => now()])->save();

            $progress = $this->journey->startProgram($profile, $batch->program);
            $progress->update(['class_batch_id' => $batch->id]);

            $this->timeline->record($profile, 'class_registered', "Registered for {$batch->fullLabel()}", null, $batch);

            return $participant;
        });
    }

    public function updateStatus(ClassParticipant $participant, ParticipantStatus $status): ClassParticipant
    {
        return DB::transaction(function () use ($participant, $status) {
            $participant->update([
                'status' => $status,
                'completed_at' => $status === ParticipantStatus::Completed ? ($participant->completed_at ?? today()) : null,
            ]);

            $progress = MemberProgramProgress::firstWhere([
                'profile_id' => $participant->profile_id,
                'discipleship_program_id' => $participant->batch->discipleship_program_id,
            ]);

            if ($progress) {
                match ($status) {
                    ParticipantStatus::Completed => $this->journey->completeProgram($progress, $participant->completed_at?->toDateString()),
                    ParticipantStatus::Incomplete => $this->journey->setStatus($progress, ProgressStatus::Incomplete),
                    ParticipantStatus::Cancelled => $progress->status !== ProgressStatus::Completed
                        ? $this->journey->setStatus($progress, ProgressStatus::NotStarted) : null,
                    default => $progress->status !== ProgressStatus::Completed
                        ? $this->journey->setStatus($progress, ProgressStatus::InProgress) : null,
                };
            }

            return $participant;
        });
    }

    /**
     * @param  array<int, string>  $attendance  participant_id => present|absent|excused
     */
    public function recordAttendance(ClassSession $session, array $attendance): void
    {
        DB::transaction(function () use ($session, $attendance) {
            $participantIds = $session->batch->participants()->pluck('id')->all();

            foreach ($attendance as $participantId => $status) {
                if (! in_array((int) $participantId, $participantIds, true) || ! AttendanceStatus::tryFrom($status)) {
                    continue;
                }
                ClassAttendance::updateOrCreate(
                    ['class_session_id' => $session->id, 'class_participant_id' => $participantId],
                    ['status' => $status],
                );
            }

            $session->batch->participants()
                ->whereIn('id', array_keys(array_filter($attendance, fn ($s) => $s === AttendanceStatus::Present->value)))
                ->whereIn('status', [ParticipantStatus::Registered->value, ParticipantStatus::Confirmed->value])
                ->update(['status' => ParticipantStatus::InProgress->value]);
        });
    }

    /** Create the session outline from the program's chapters / configured session count. */
    public function generateSessions(ClassBatch $batch): int
    {
        if ($batch->sessions()->exists()) {
            return 0;
        }
        $program = $batch->program()->with('chapters')->first();
        $topics = $program->chapters->isNotEmpty()
            ? $program->chapters->map(fn ($c) => ['n' => $c->number, 't' => $c->title])
            : collect(range(1, max(1, (int) $program->total_sessions)))->map(fn ($n) => ['n' => $n, 't' => "Session {$n}"]);

        foreach ($topics as $i => $topic) {
            $batch->sessions()->create([
                'session_number' => $topic['n'],
                'topic' => $topic['t'],
                'session_date' => $batch->start_date?->copy()->addWeeks($i),
                'facilitator_profile_id' => $batch->facilitator_profile_id,
                'room' => $batch->location,
            ]);
        }

        return $topics->count();
    }

    /** @return array{present: int, total: int, rate: ?int} */
    public function attendanceRate(ClassParticipant $participant): array
    {
        $rows = $participant->attendances;
        $present = $rows->where('status', AttendanceStatus::Present)->count();

        return ['present' => $present, 'total' => $rows->count(), 'rate' => $rows->count() ? (int) round($present / $rows->count() * 100) : null];
    }
}
