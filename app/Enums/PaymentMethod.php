<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use Concerns;

    case BankTransfer = 'bank_transfer';
    case PayOnPickup = 'pay_on_pickup';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transfer bank / QRIS',
            self::PayOnPickup => 'Bayar saat ambil (tunai / QRIS)',
        };
    }
}
