<?php

namespace App\Enums;

enum JoinRequestStatus: string
{
    use Concerns;

    case Pending = 'pending';
    case Contacted = 'contacted';
    case Approved = 'approved';
    case Joined = 'joined';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Contacted => 'Contacted',
            self::Approved => 'Approved',
            self::Joined => 'Joined',
            self::Rejected => 'Not Continuing',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Contacted => 'indigo',
            self::Approved => 'blue',
            self::Joined => 'green',
            self::Rejected => 'gray',
        };
    }
}
