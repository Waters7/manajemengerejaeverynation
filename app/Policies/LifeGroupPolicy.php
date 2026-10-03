<?php

namespace App\Policies;

use App\Models\LifeGroup;
use App\Models\User;
use App\Services\AccessScope;

/**
 * Leaders only reach their own LifeGroup; Campus Ministry reaches campus groups; Pastors reach all.
 */
class LifeGroupPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('lifegroups.view');
    }

    public function view(User $user, LifeGroup $lifeGroup): bool
    {
        return $user->can('lifegroups.view')
            && $this->scope->lifeGroups(LifeGroup::whereKey($lifeGroup->id), $user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('lifegroups.manage') && ($this->scope->isChurchWide($user) || $this->scope->campusIds($user) !== []);
    }

    public function update(User $user, LifeGroup $lifeGroup): bool
    {
        return $user->can('lifegroups.manage') && $this->scope->canManageLifeGroup($user, $lifeGroup);
    }

    public function delete(User $user, LifeGroup $lifeGroup): bool
    {
        return $user->can('lifegroups.manage') && $this->scope->isChurchWide($user);
    }
}
