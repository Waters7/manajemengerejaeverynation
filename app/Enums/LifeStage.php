<?php

namespace App\Enums;

enum LifeStage: string
{
    use Concerns;

    case Pelajar = 'pelajar';
    case Mahasiswa = 'mahasiswa';
    case YoungProfessional = 'young_professional';
    case Professional = 'professional';
    case Married = 'married';
    case Family = 'family';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pelajar => 'Pelajar',
            self::Mahasiswa => 'Mahasiswa',
            self::YoungProfessional => 'Young Professional',
            self::Professional => 'Professional',
            self::Married => 'Married',
            self::Family => 'Family',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pelajar => 'gray',
            self::Mahasiswa => 'gray',
            self::YoungProfessional => 'gray',
            self::Professional => 'gray',
            self::Married => 'gray',
            self::Family => 'gray',
            self::Other => 'gray',
        };
    }
}
