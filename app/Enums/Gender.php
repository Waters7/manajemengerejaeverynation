<?php

namespace App\Enums;

enum Gender: string
{
    use Concerns;

    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Laki-laki',
            self::Female => 'Perempuan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Male => 'gray',
            self::Female => 'gray',
        };
    }
}
