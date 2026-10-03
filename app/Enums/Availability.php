<?php

namespace App\Enums;

enum Availability: string
{
    use Concerns;

    case Sunday = 'sunday';
    case Weekday = 'weekday';
    case Weekend = 'weekend';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Sunday => 'Sunday',
            self::Weekday => 'Weekday',
            self::Weekend => 'Weekend',
            self::Flexible => 'Flexible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sunday => 'gray',
            self::Weekday => 'gray',
            self::Weekend => 'gray',
            self::Flexible => 'gray',
        };
    }
}
