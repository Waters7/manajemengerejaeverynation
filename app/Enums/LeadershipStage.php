<?php

namespace App\Enums;

enum LeadershipStage: string
{
    use Concerns;

    case Potential = 'potential';
    case Training = 'training';
    case Ready = 'ready';
    case Approved = 'approved';
    case ActiveLeader = 'active_leader';

    public function label(): string
    {
        return match ($this) {
            self::Potential => 'Potential Leader',
            self::Training => 'Leadership Training',
            self::Ready => 'Ready to Lead',
            self::Approved => 'Approved',
            self::ActiveLeader => 'Active Leader',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Potential => 'gray',
            self::Training => 'blue',
            self::Ready => 'indigo',
            self::Approved => 'green',
            self::ActiveLeader => 'green',
        };
    }
}
