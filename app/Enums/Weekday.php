<?php

namespace App\Enums;

enum Weekday: string
{
    use Concerns;

    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Senin',
            self::Tuesday => 'Selasa',
            self::Wednesday => 'Rabu',
            self::Thursday => 'Kamis',
            self::Friday => 'Jumat',
            self::Saturday => 'Sabtu',
            self::Sunday => 'Minggu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Monday => 'gray',
            self::Tuesday => 'gray',
            self::Wednesday => 'gray',
            self::Thursday => 'gray',
            self::Friday => 'gray',
            self::Saturday => 'gray',
            self::Sunday => 'gray',
        };
    }
}
