<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    use Concerns;

    case Everyone = 'everyone';
    case Members = 'members';
    case Leaders = 'leaders';
    case MinistryTeam = 'ministry_team';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Everyone',
            self::Members => 'Members',
            self::Leaders => 'Leaders',
            self::MinistryTeam => 'Ministry Team',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Everyone => 'gray',
            self::Members => 'gray',
            self::Leaders => 'gray',
            self::MinistryTeam => 'gray',
        };
    }
}
