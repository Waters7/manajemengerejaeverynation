<?php

namespace App\Enums;

enum ProgressStatus: string
{
    use Concerns;

    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Incomplete = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Incomplete => 'Incomplete',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::Incomplete => 'amber',
        };
    }
}
