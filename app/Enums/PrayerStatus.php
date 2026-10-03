<?php

namespace App\Enums;

enum PrayerStatus: string
{
    use Concerns;

    case New = 'new';
    case Praying = 'praying';
    case FollowUp = 'follow_up';
    case Answered = 'answered';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Praying => 'Praying',
            self::FollowUp => 'Follow-Up',
            self::Answered => 'Answered',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Praying => 'indigo',
            self::FollowUp => 'amber',
            self::Answered => 'green',
        };
    }
}
