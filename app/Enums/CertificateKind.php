<?php

namespace App\Enums;

enum CertificateKind: string
{
    use Concerns;

    case Baptism = 'baptism';
    case Program = 'program';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Baptism => 'Baptism certificate',
            self::Program => 'Program certificate',
            self::Other => 'Other certificate',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baptism => 'blue',
            self::Program => 'green',
            self::Other => 'gray',
        };
    }
}
