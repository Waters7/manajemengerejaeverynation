<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Notifications\TeamAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Sends TeamAlert notifications to users holding a permission or to specific users.
 */
class TeamNotifier
{
    /** @return Collection<int, User> */
    public function usersWithPermission(string $permission): Collection
    {
        return User::permission($permission)
            ->where('account_status', AccountStatus::Active->value)
            ->get();
    }

    public function toPermission(string $permission, TeamAlert $alert, ?User $except = null): void
    {
        $users = $this->usersWithPermission($permission)->reject(fn (User $u) => $except && $u->is($except));
        Notification::send($users, $alert);
    }

    public function toUser(?User $user, TeamAlert $alert): void
    {
        $user?->notify($alert);
    }
}
