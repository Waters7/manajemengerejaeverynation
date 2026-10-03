<?php

namespace App\Enums;

enum LifeGroupCategory: string
{
    use Concerns;

    case Youth = 'youth';
    case Campus = 'campus';
    case YoungProfessionals = 'young_professionals';
    case Men = 'men';
    case Women = 'women';
    case Couples = 'couples';
    case Family = 'family';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Youth => 'Youth',
            self::Campus => 'Campus',
            self::YoungProfessionals => 'Young Professionals',
            self::Men => 'Men',
            self::Women => 'Women',
            self::Couples => 'Couples',
            self::Family => 'Family',
            self::Mixed => 'Mixed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Youth => 'gray',
            self::Campus => 'gray',
            self::YoungProfessionals => 'gray',
            self::Men => 'gray',
            self::Women => 'gray',
            self::Couples => 'gray',
            self::Family => 'gray',
            self::Mixed => 'gray',
        };
    }
}
