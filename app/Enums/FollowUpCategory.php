<?php

namespace App\Enums;

enum FollowUpCategory: string
{
    use Concerns;

    case Newcomer = 'newcomer';
    case Involvement = 'involvement';
    case LifegroupRequest = 'lifegroup_request';
    case Volunteer = 'volunteer';
    case Discipleship = 'discipleship';
    case ClassProgram = 'class';
    case Care = 'care';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Newcomer => 'Newcomer',
            self::Involvement => 'Get Involved',
            self::LifegroupRequest => 'LifeGroup Request',
            self::Volunteer => 'Volunteer',
            self::Discipleship => 'Discipleship',
            self::ClassProgram => 'Class',
            self::Care => 'Care',
            self::General => 'General',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Newcomer => 'blue',
            self::Involvement => 'blue',
            self::LifegroupRequest => 'indigo',
            self::Volunteer => 'indigo',
            self::Discipleship => 'green',
            self::ClassProgram => 'green',
            self::Care => 'amber',
            self::General => 'gray',
        };
    }
}
