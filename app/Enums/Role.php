<?php

namespace App\Enums;

enum Role: string
{
    use Concerns;

    case SuperAdmin = 'super-admin';
    case Pastor = 'pastor';
    case CampusMinistry = 'campus-ministry';
    case Leader = 'leader';
    case MinistryCoordinator = 'ministry-coordinator';
    case WelcomeTeam = 'welcome-team';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Pastor => 'Pastor',
            self::CampusMinistry => 'Campus Ministry',
            self::Leader => 'Leader',
            self::MinistryCoordinator => 'Ministry Coordinator',
            self::WelcomeTeam => 'Welcome Team',
            self::User => 'User',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'gray',
            self::Pastor => 'gray',
            self::CampusMinistry => 'gray',
            self::Leader => 'gray',
            self::MinistryCoordinator => 'gray',
            self::WelcomeTeam => 'gray',
            self::User => 'gray',
        };
    }
}
