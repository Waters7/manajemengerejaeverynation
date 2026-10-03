<?php

namespace App\Policies;

use App\Models\Ministry;
use App\Models\User;
use App\Services\AccessScope;

/**
 * Coordinators manage their own ministries; pastors can read every ministry.
 */
class MinistryPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function view(User $user, Ministry $ministry): bool
    {
        return $user->can('ministries.view')
            && ($this->scope->isChurchWide($user) || in_array($ministry->id, $this->scope->ministryIds($user), true));
    }

    public function create(User $user): bool
    {
        return $user->can('ministries.manage') && $this->scope->isChurchWide($user);
    }

    public function update(User $user, Ministry $ministry): bool
    {
        return $user->can('ministries.manage')
            && ($this->scope->isChurchWide($user) || in_array($ministry->id, $this->scope->ministryIds($user), true));
    }

    public function delete(User $user, Ministry $ministry): bool
    {
        return $this->create($user);
    }
}
