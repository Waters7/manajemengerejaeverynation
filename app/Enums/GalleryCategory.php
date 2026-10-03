<?php

namespace App\Enums;

enum GalleryCategory: string
{
    use Concerns;

    case SundayService = 'sunday_service';
    case Lifegroup = 'lifegroup';
    case CampusMinistry = 'campus_ministry';
    case Discipleship = 'discipleship';
    case Community = 'community';
    case SpecialEvent = 'special_event';

    public function label(): string
    {
        return match ($this) {
            self::SundayService => 'Sunday Service',
            self::Lifegroup => 'LifeGroup',
            self::CampusMinistry => 'Campus Ministry',
            self::Discipleship => 'Discipleship',
            self::Community => 'Community',
            self::SpecialEvent => 'Special Event',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SundayService => 'gray',
            self::Lifegroup => 'gray',
            self::CampusMinistry => 'gray',
            self::Discipleship => 'gray',
            self::Community => 'gray',
            self::SpecialEvent => 'gray',
        };
    }
}
