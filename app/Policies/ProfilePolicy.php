<?php

namespace App\Policies;

use App\Models\DisciplerRelationship;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccessScope;

/**
 * Who may see or care for a person. Super Admin is allowed through Gate::before.
 */
class ProfilePolicy
{
    public function __construct(private AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function view(User $user, Profile $profile): bool
    {
        return $user->can('members.view') && $this->scope->canSeeProfile($user, $profile);
    }

    public function create(User $user): bool
    {
        return $user->can('members.manage');
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->can('members.manage') && $this->scope->canSeeProfile($user, $profile);
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->hasChurchWideAccess() && $user->can('members.manage');
    }

    /** Update discipleship progress, record meetings and notes for this person. */
    public function disciple(User $user, Profile $profile): bool
    {
        if ($this->isDirectDiscipler($user, $profile)) {
            return true;
        }

        return $user->can('discipleship.manage') && $this->scope->canSeeProfile($user, $profile);
    }

    /** Internal follow-up / pastoral notes are never visible to the person themselves or other members. */
    public function viewInternalNotes(User $user, Profile $profile): bool
    {
        return $user->canAccessAdmin() && $this->view($user, $profile) || $this->isDirectDiscipler($user, $profile);
    }

    public function isDirectDiscipler(User $user, Profile $profile): bool
    {
        $mine = $this->scope->profileId($user);

        return $mine !== null && DisciplerRelationship::active()
            ->where('discipler_profile_id', $mine)
            ->where('disciple_profile_id', $profile->id)
            ->exists();
    }
}
