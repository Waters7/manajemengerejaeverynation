<?php

namespace App\Enums;

enum PrayerVisibility: string
{
    use Concerns;

    case PastorOnly = 'pastor_only';
    case LifegroupLeader = 'lifegroup_leader';
    case PrayerTeam = 'prayer_team';

    public function label(): string
    {
        return match ($this) {
            self::PastorOnly => 'Pastor Only',
            self::LifegroupLeader => 'LifeGroup Leader',
            self::PrayerTeam => 'Prayer Team',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PastorOnly => 'red',
            self::LifegroupLeader => 'blue',
            self::PrayerTeam => 'indigo',
        };
    }
}
