<?php

namespace App\Enums;

/**
 * Shared helpers for string-backed enums used in forms, filters and badges.
 */
trait Concerns
{
    /** @return array<string, string> value => label */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function color(): string
    {
        return 'gray';
    }
}
