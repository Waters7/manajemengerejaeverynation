<?php

namespace App\Policies;

use App\Models\PastoralCareRequest;
use App\Models\User;

/**
 * Pastoral care is highly sensitive: only roles holding `pastoral.view` may see it at all.
 */
class PastoralCareRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pastoral.view');
    }

    public function view(User $user, PastoralCareRequest $care): bool
    {
        return $user->can('pastoral.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pastoral.manage');
    }

    public function update(User $user, PastoralCareRequest $care): bool
    {
        return $user->can('pastoral.manage') || ($care->assigned_to === $user->id && $user->can('pastoral.view'));
    }
}
