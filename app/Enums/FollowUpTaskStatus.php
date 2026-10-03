<?php

namespace App\Enums;

enum FollowUpTaskStatus: string
{
    use Concerns;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::InProgress => 'amber',
            self::Done => 'green',
            self::Cancelled => 'gray',
        };
    }
}
