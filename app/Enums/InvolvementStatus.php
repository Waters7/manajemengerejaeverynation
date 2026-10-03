<?php

namespace App\Enums;

enum InvolvementStatus: string
{
    use Concerns;

    case New = 'new';
    case Contacted = 'contacted';
    case FollowUp = 'follow_up';
    case Connected = 'connected';
    case Active = 'active';
    case NotContinuing = 'not_continuing';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::FollowUp => 'Follow-Up',
            self::Connected => 'Connected',
            self::Active => 'Active',
            self::NotContinuing => 'Not Continuing',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'indigo',
            self::FollowUp => 'amber',
            self::Connected => 'green',
            self::Active => 'green',
            self::NotContinuing => 'gray',
        };
    }
}
