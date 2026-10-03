<?php

namespace App\Enums;

enum MemberStatus: string
{
    use Concerns;

    case Visitor = 'visitor';
    case Newcomer = 'newcomer';
    case Connected = 'connected';
    case Member = 'member';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Visitor => 'Visitor',
            self::Newcomer => 'Newcomer',
            self::Connected => 'Connected',
            self::Member => 'Active Member',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Visitor => 'gray',
            self::Newcomer => 'blue',
            self::Connected => 'indigo',
            self::Member => 'green',
            self::Inactive => 'gray',
        };
    }
}
