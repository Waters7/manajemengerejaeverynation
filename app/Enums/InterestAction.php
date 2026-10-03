<?php

namespace App\Enums;

enum InterestAction: string
{
    use Concerns;

    case KnowChurch = 'know_church';
    case SundayService = 'sunday_service';
    case Lifegroup = 'lifegroup';
    case One2one = 'one2one';
    case Discipleship = 'discipleship';
    case DiscipleshipClass = 'class';
    case Campus = 'campus';
    case Volunteer = 'volunteer';
    case Ministry = 'ministry';
    case Event = 'event';
    case Prayer = 'prayer';
    case Pastoral = 'pastoral';
    case KnowJesus = 'know_jesus';

    public function label(): string
    {
        return match ($this) {
            self::KnowChurch => 'Mengenal gereja',
            self::SundayService => 'Sunday Service',
            self::Lifegroup => 'LifeGroup',
            self::One2one => 'One 2 One',
            self::Discipleship => 'Discipleship',
            self::DiscipleshipClass => 'Discipleship Class',
            self::Campus => 'Campus Ministry',
            self::Volunteer => 'Volunteer',
            self::Ministry => 'Ministry',
            self::Event => 'Event',
            self::Prayer => 'Prayer Request',
            self::Pastoral => 'Pastoral Follow-Up',
            self::KnowJesus => 'Know Jesus',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::KnowChurch => 'gray',
            self::SundayService => 'gray',
            self::Lifegroup => 'gray',
            self::One2one => 'gray',
            self::Discipleship => 'gray',
            self::DiscipleshipClass => 'gray',
            self::Campus => 'gray',
            self::Volunteer => 'gray',
            self::Ministry => 'gray',
            self::Event => 'gray',
            self::Prayer => 'gray',
            self::Pastoral => 'gray',
            self::KnowJesus => 'gray',
        };
    }
}
