<?php

namespace App\Enums;

enum NewcomerJourney: string
{
    use Concerns;

    case FirstVisit = 'first_visit';
    case ConnectCard = 'connect_card';
    case Contacted = 'contacted';
    case Connected = 'connected';
    case Lifegroup = 'lifegroup';
    case One2one = 'one2one';
    case Discipleship = 'discipleship';

    public function label(): string
    {
        return match ($this) {
            self::FirstVisit => 'First Visit',
            self::ConnectCard => 'Connect Card',
            self::Contacted => 'Contacted',
            self::Connected => 'Connected',
            self::Lifegroup => 'LifeGroup',
            self::One2one => 'One 2 One',
            self::Discipleship => 'Discipleship',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FirstVisit => 'gray',
            self::ConnectCard => 'blue',
            self::Contacted => 'indigo',
            self::Connected => 'indigo',
            self::Lifegroup => 'green',
            self::One2one => 'green',
            self::Discipleship => 'green',
        };
    }
}
