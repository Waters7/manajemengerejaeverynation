<?php

namespace App\Enums;

enum InvolvementType: string
{
    use Concerns;

    case GetInvolved = 'get_involved';
    case ConnectCard = 'connect_card';

    public function label(): string
    {
        return match ($this) {
            self::GetInvolved => 'Get Involved',
            self::ConnectCard => 'Connect Card',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GetInvolved => 'gray',
            self::ConnectCard => 'gray',
        };
    }
}
