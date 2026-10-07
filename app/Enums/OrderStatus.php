<?php

namespace App\Enums;

enum OrderStatus: string
{
    use Concerns;

    case PendingPayment = 'pending_payment';
    case WaitingConfirmation = 'waiting_confirmation';
    case Paid = 'paid';
    case Processing = 'processing';
    case Ready = 'ready';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu pembayaran',
            self::WaitingConfirmation => 'Menunggu konfirmasi',
            self::Paid => 'Sudah dibayar',
            self::Processing => 'Diproses',
            self::Ready => 'Siap diambil',
            self::Shipped => 'Dikirim',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'amber',
            self::WaitingConfirmation => 'blue',
            self::Paid, self::Processing => 'indigo',
            self::Ready, self::Shipped => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'gray',
        };
    }

    /** Orders that still need action from the store team. */
    public static function open(): array
    {
        return [self::PendingPayment->value, self::WaitingConfirmation->value, self::Paid->value, self::Processing->value, self::Ready->value, self::Shipped->value];
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
