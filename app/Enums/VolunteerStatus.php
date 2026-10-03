<?php

namespace App\Enums;

enum VolunteerStatus: string
{
    use Concerns;

    case Applicant = 'applicant';
    case Orientation = 'orientation';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Applicant',
            self::Orientation => 'Orientation',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Applicant => 'gray',
            self::Orientation => 'amber',
            self::Active => 'green',
            self::Inactive => 'gray',
        };
    }
}
