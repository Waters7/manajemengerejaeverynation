<?php

namespace App\Enums;

enum CarePriority: string
{
    use Concerns;

    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'gray',
            self::High => 'amber',
            self::Urgent => 'red',
        };
    }
}
