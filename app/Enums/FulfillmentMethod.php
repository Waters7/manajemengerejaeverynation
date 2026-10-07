<?php

namespace App\Enums;

enum FulfillmentMethod: string
{
    use Concerns;

    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Ambil di gereja',
            self::Delivery => 'Dikirim',
        };
    }
}
