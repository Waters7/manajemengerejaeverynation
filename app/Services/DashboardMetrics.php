<?php

namespace App\Services;

use App\Enums\LeadershipStage;
use App\Enums\MemberStatus;
use App\Enums\ProgressStatus;
use App\Models\ClassBatch;
use App\Models\DisciplerRelationship;
use App\Models\LeadershipCandidate;
use App\Models\LifeGroup;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Models\User;

/**
 * Headline numbers for the dashboards, always narrowed to what the user may see.
 */
class DashboardMetrics
{
    public function __construct(
        private AccessScope $scope,
        private CareRadar $radar,
    ) {}

    /**
     * @return array<string, int>
     */
    public function forUser(User $user): array
    {
        $people = fn () => $this->scope->profiles(Profile::query(), $user);
        $scopedProfileIds = fn () => $this->scope->profiles(Profile::query(), $user)->select('profiles.id');

        return [
            'active_members' => $people()->where('member_status', MemberStatus::Member->value)->count(),
            'newcomers' => $people()->where('member_status', MemberStatus::Newcomer->value)->count(),
            'active_lifegroups' => $this->scope->lifeGroups(LifeGroup::active(), $user)->count(),
            'being_discipled' => MemberProgramProgress::where('status', ProgressStatus::InProgress->value)
                ->whereIn('profile_id', $scopedProfileIds())->distinct()->count('profile_id'),
            'disciplers' => DisciplerRelationship::active()->whereIn('disciple_profile_id', $scopedProfileIds())
                ->distinct()->count('discipler_profile_id'),
            'potential_leaders' => LeadershipCandidate::where('stage', '!=', LeadershipStage::ActiveLeader->value)
                ->whereIn('profile_id', $scopedProfileIds())->count(),
            'upcoming_classes' => $this->scope->classBatches(ClassBatch::upcoming(), $user)->count(),
            'needs_follow_up' => $this->radar->total($user),
        ];
    }
}
