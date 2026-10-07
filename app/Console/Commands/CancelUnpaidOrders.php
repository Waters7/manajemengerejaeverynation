<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Cancels bank-transfer store orders that stayed unpaid past the window in Store settings,
 * returning their reserved stock.
 */
class CancelUnpaidOrders extends Command
{
    protected $signature = 'store:cancel-unpaid';

    protected $description = 'Cancel unpaid bank-transfer store orders and return their stock';

    public function handle(OrderService $orders): int
    {
        $cancelled = $orders->cancelExpired();
        $this->info("{$cancelled} unpaid order(s) cancelled.");

        return self::SUCCESS;
    }
}
