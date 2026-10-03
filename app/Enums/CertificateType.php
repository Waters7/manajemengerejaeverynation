<?php

namespace App\Enums;

/**
 * How a curriculum program is recognised.
 */
enum CertificateType: string
{
    use Concerns;

    case Count = 'count';
    case Once = 'once';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Journey record (count how many times, for yourself and for others)',
            self::Once => 'One-time certificate',
            self::None => 'No certificate',
        };
    }
}
