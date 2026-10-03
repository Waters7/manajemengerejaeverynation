<?php

namespace App\Enums;

enum ContactType: string
{
    use Concerns;

    case Note = 'note';
    case Whatsapp = 'whatsapp';
    case Call = 'call';
    case Meeting = 'meeting';
    case Visit = 'visit';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Whatsapp => 'WhatsApp',
            self::Call => 'Call',
            self::Meeting => 'Meeting',
            self::Visit => 'Visit',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Note => 'gray',
            self::Whatsapp => 'gray',
            self::Call => 'gray',
            self::Meeting => 'gray',
            self::Visit => 'gray',
        };
    }
}
