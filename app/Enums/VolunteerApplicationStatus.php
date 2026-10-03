<?php

namespace App\Enums;

enum VolunteerApplicationStatus: string
{
    use Concerns;

    case Submitted = 'submitted';
    case Contacted = 'contacted';
    case Interview = 'interview';
    case Orientation = 'orientation';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Contacted => 'Contacted',
            self::Interview => 'Interview',
            self::Orientation => 'Orientation',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'blue',
            self::Contacted => 'indigo',
            self::Interview => 'indigo',
            self::Orientation => 'amber',
            self::Accepted => 'green',
            self::Declined => 'gray',
        };
    }
}
