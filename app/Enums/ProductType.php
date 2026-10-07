<?php

namespace App\Enums;

enum ProductType: string
{
    use Concerns;

    case Book = 'book';
    case Merchandise = 'merchandise';

    public function label(): string
    {
        return match ($this) {
            self::Book => 'Buku',
            self::Merchandise => 'Merchandise',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Book => 'blue',
            self::Merchandise => 'indigo',
        };
    }
}
