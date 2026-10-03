<?php

namespace App\Enums;

enum ParticipantStatus: string
{
    use Concerns;

    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Incomplete = 'incomplete';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::Confirmed => 'Confirmed',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Incomplete => 'Incomplete',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Registered => 'gray',
            self::Confirmed => 'blue',
            self::InProgress => 'indigo',
            self::Completed => 'green',
            self::Incomplete => 'amber',
            self::Cancelled => 'gray',
        };
    }
}
