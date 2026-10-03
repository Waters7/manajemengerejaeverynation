<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    use Concerns;

    case Registered = 'registered';
    case WaitingList = 'waiting_list';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::WaitingList => 'Waiting List',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Registered => 'green',
            self::WaitingList => 'amber',
            self::Cancelled => 'gray',
        };
    }
}
