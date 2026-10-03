<?php

namespace App\Enums;

enum BatchStatus: string
{
    use Concerns;

    case Planned = 'planned';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Ongoing => 'Ongoing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Ongoing => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'gray',
        };
    }
}
