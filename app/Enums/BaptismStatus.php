<?php

namespace App\Enums;

enum BaptismStatus: string
{
    use Concerns;

    case NotYet = 'not_yet';
    case Scheduled = 'scheduled';
    case Baptized = 'baptized';

    public function label(): string
    {
        return match ($this) {
            self::NotYet => 'Belum dibaptis',
            self::Scheduled => 'Baptisan dijadwalkan',
            self::Baptized => 'Sudah dibaptis',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotYet => 'gray',
            self::Scheduled => 'amber',
            self::Baptized => 'green',
        };
    }
}
