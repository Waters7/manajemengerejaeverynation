<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    use Concerns;

    case Present = 'present';
    case Absent = 'absent';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Excused => 'Excused',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'green',
            self::Absent => 'gray',
            self::Excused => 'amber',
        };
    }
}
