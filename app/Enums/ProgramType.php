<?php

namespace App\Enums;

enum ProgramType: string
{
    use Concerns;

    case Book = 'book';
    case ClassProgram = 'class';
    case Training = 'training';
    case Event = 'event';

    public function label(): string
    {
        return match ($this) {
            self::Book => 'Book',
            self::ClassProgram => 'Class',
            self::Training => 'Training',
            self::Event => 'Event',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Book => 'blue',
            self::ClassProgram => 'indigo',
            self::Training => 'green',
            self::Event => 'amber',
        };
    }
}
