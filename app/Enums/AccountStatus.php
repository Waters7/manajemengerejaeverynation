<?php

namespace App\Enums;

enum AccountStatus: string
{
    use Concerns;

    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Pending Verification',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingVerification => 'amber',
            self::Active => 'green',
            self::Inactive => 'gray',
        };
    }
}
