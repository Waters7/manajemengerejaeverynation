<?php

namespace App\Policies;

use App\Models\InvolvementRequest;
use App\Models\User;
use App\Services\AccessScope;

class InvolvementRequestPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function view(User $user, InvolvementRequest $request): bool
    {
        return $user->can('involvement.view')
            && $this->scope->involvementRequests(InvolvementRequest::query()->whereKey($request->id), $user)->exists();
    }

    /** Assigned follow-up people may always work their own requests. */
    public function update(User $user, InvolvementRequest $request): bool
    {
        if ($request->assigned_to === $user->id) {
            return true;
        }

        return $user->can('involvement.manage') && $this->view($user, $request);
    }
}
