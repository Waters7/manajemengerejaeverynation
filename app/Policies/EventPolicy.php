<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Services\AccessScope;

/**
 * Campus Ministry manages campus events; pastors and admins manage all events.
 */
class EventPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function update(User $user, Event $event): bool
    {
        return $user->can('events.manage')
            && $this->scope->events(Event::withTrashed()->whereKey($event->id), $user)->exists();
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
