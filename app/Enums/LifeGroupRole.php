<?php

namespace App\Enums;

enum LifeGroupRole: string
{
    use Concerns;

    case Leader = 'leader';
    case CoLeader = 'co_leader';
    case Apprentice = 'apprentice';
    case Member = 'member';
    case Visitor = 'visitor';

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Leader',
            self::CoLeader => 'Co-Leader',
            self::Apprentice => 'Apprentice',
            self::Member => 'Member',
            self::Visitor => 'Visitor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Leader => 'blue',
            self::CoLeader => 'indigo',
            self::Apprentice => 'indigo',
            self::Member => 'green',
            self::Visitor => 'gray',
        };
    }
}
