<?php

namespace App\Enums;

enum DiscoverySource: string
{
    use Concerns;

    case Friend = 'friend';
    case Family = 'family';
    case SocialMedia = 'social_media';
    case CampusMinistry = 'campus_ministry';
    case Event = 'event';
    case SundayService = 'sunday_service';
    case Lifegroup = 'lifegroup';
    case Search = 'search';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Friend => 'Teman',
            self::Family => 'Keluarga',
            self::SocialMedia => 'Social Media',
            self::CampusMinistry => 'Campus Ministry',
            self::Event => 'Event',
            self::SundayService => 'Sunday Service',
            self::Lifegroup => 'LifeGroup',
            self::Search => 'Google/Search',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Friend => 'gray',
            self::Family => 'gray',
            self::SocialMedia => 'gray',
            self::CampusMinistry => 'gray',
            self::Event => 'gray',
            self::SundayService => 'gray',
            self::Lifegroup => 'gray',
            self::Search => 'gray',
            self::Other => 'gray',
        };
    }
}
