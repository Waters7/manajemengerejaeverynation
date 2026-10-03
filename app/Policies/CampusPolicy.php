<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;
use App\Services\AccessScope;

/**
 * Campus Ministry users only manage the campuses assigned to them.
 */
class CampusPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function view(User $user, Campus $campus): bool
    {
        return $user->can('campus.view')
            && ($this->scope->isChurchWide($user) || in_array($campus->id, $this->scope->campusIds($user), true));
    }

    public function create(User $user): bool
    {
        return $user->can('campus.manage') && $this->scope->isChurchWide($user);
    }

    public function update(User $user, Campus $campus): bool
    {
        return $user->can('campus.manage') && $this->view($user, $campus);
    }

    public function delete(User $user, Campus $campus): bool
    {
        return $this->create($user);
    }
}
