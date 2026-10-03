<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use App\Services\AccessScope;

/**
 * A certificate belongs to one person: they can always open it; the church team can open
 * and manage certificates of people in their care.
 */
class CertificatePolicy
{
    public function __construct(private AccessScope $scope) {}

    public function view(User $user, Certificate $certificate): bool
    {
        return $this->scope->profileId($user) === $certificate->profile_id
            || ($user->can('members.view') && $this->scope->canSeeProfile($user, $certificate->profile));
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.manage') && $this->scope->canSeeProfile($user, $certificate->profile);
    }
}
