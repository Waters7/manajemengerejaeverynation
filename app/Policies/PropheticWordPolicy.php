<?php

namespace App\Policies;

use App\Models\PropheticWord;
use App\Models\User;
use App\Services\AccessScope;

/**
 * Prophetic words are personal: only the person and staff holding `prophecy.manage`
 * (for people they may see) can listen to them.
 */
class PropheticWordPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function view(User $user, PropheticWord $word): bool
    {
        return $this->scope->profileId($user) === $word->profile_id || $this->delete($user, $word);
    }

    public function delete(User $user, PropheticWord $word): bool
    {
        return $user->can('prophecy.manage') && $this->scope->canSeeProfile($user, $word->profile);
    }
}
