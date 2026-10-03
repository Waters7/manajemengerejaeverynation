<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\LeadershipStage;
use App\Enums\ProgressStatus;
use App\Models\DiscipleshipProgram;
use App\Models\LeadershipCandidate;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Potential Leader → Leadership Training → Ready to Lead → Approved → Active Leader.
 * Moving into Approved / Active Leader is a pastoral decision (`leadership.approve`).
 */
class LeadershipService
{
    public function __construct(
        private TimelineRecorder $timeline,
        private AuditLogger $audit,
    ) {}

    public function nominate(Profile $profile, ?string $recommendation = null): LeadershipCandidate
    {
        return DB::transaction(function () use ($profile, $recommendation) {
            $candidate = LeadershipCandidate::firstOrCreate(
                ['profile_id' => $profile->id],
                ['stage' => LeadershipStage::Potential, 'recommended_by' => Auth::id(), 'recommendation' => $recommendation],
            );

            if ($candidate->wasRecentlyCreated) {
                $this->timeline->record($profile, 'leadership', 'Recognised as a potential leader', null, $candidate);
            } elseif ($recommendation) {
                $candidate->update(['recommendation' => trim($candidate->recommendation."\n\n".$recommendation)]);
            }

            return $candidate;
        });
    }

    public function moveTo(LeadershipCandidate $candidate, LeadershipStage $stage, User $actor, ?string $notes = null): LeadershipCandidate
    {
        $requiresApproval = in_array($stage, [LeadershipStage::Approved, LeadershipStage::ActiveLeader], true);
        if ($requiresApproval && ! $actor->can('leadership.approve')) {
            throw new AuthorizationException('Only a pastor or admin can approve leaders.');
        }

        return DB::transaction(function () use ($candidate, $stage, $actor, $notes, $requiresApproval) {
            $candidate->fill(['stage' => $stage, 'notes' => $notes ?? $candidate->notes]);
            if ($requiresApproval) {
                $candidate->fill(['decided_by' => $actor->id, 'decided_at' => now()]);
            }
            $candidate->save();

            if ($requiresApproval) {
                $this->audit->log(AuditAction::Approve, $candidate, "Leadership: {$candidate->profile->full_name} → {$stage->label()}");
            }
            if ($stage === LeadershipStage::ActiveLeader) {
                $this->timeline->record($candidate->profile, 'leader', 'Became a leader', null, $candidate);
            }

            return $candidate;
        });
    }

    /**
     * Snapshot shown on a candidate's card.
     *
     * @return array{lifegroup: ?string, stage: ?string, completed: list<string>, l113: string, l215: string, disciples: int}
     */
    public function snapshot(Profile $profile): array
    {
        $profile->loadMissing(['activeLifeGroups', 'currentStage', 'programProgress.program']);
        $completed = $profile->programProgress->where('status', ProgressStatus::Completed)->pluck('program.name')->filter()->values()->all();

        $statusOf = function (string $slug) use ($profile): string {
            $programId = DiscipleshipProgram::where('slug', $slug)->value('id');
            $row = $profile->programProgress->firstWhere('discipleship_program_id', $programId);

            return $row?->status->label() ?? ProgressStatus::NotStarted->label();
        };

        return [
            'lifegroup' => $profile->activeLifeGroups->first()?->name,
            'stage' => $profile->currentStage?->name,
            'completed' => $completed,
            'l113' => $statusOf('leadership-113'),
            'l215' => $statusOf('leadership-215'),
            'disciples' => $profile->discipleRelationships()->where('status', 'active')->count(),
        ];
    }
}
