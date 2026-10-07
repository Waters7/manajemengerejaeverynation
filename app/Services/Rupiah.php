<?php

namespace App\Services;

/**
 * Indonesian Rupiah formatting: 125000 → "Rp125.000".
 */
class Rupiah
{
    public static function format(int|float|null $amount): string
    {
        return 'Rp'.number_format((int) round((float) $amount), 0, ',', '.');
    }
}
