<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\TeamAlert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Store orders: checkout (server-side prices, stock reserved under row locks),
 * payment proof, confirmation, fulfilment steps and cancellation (stock returned).
 */
class OrderService
{
    public const DISK = 'local';

    /** Status steps the team can move an order to, per current status. */
    private const TRANSITIONS = [
        'pending_payment' => ['paid', 'processing', 'ready', 'shipped', 'completed'],
        'waiting_confirmation' => ['paid', 'processing', 'ready', 'shipped', 'completed'],
        'paid' => ['processing', 'ready', 'shipped', 'completed'],
        'processing' => ['ready', 'shipped', 'completed'],
        'ready' => ['completed', 'processing'],
        'shipped' => ['completed', 'processing'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private Settings $settings,
        private TeamNotifier $notifier,
        private AuditLogger $audit,
        private WhatsApp $whatsapp,
    ) {}

    /**
     * Place an order from cart lines. Prices and stock are re-read under lock, so
     * nothing the browser sends can change what is charged.
     *
     * @param  Collection<string, array{product: Product, variant: ?ProductVariant, quantity: int}>  $lines
     * @param  array{customer_name: string, whatsapp: string, email?: ?string, fulfillment: string, shipping_address?: ?string, shipping_area?: ?string, notes?: ?string, payment_method: string}  $customer
     */
    public function place(Collection $lines, array $customer, ?User $user = null): Order
    {
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Keranjang kamu masih kosong.']);
        }

        $fulfillment = FulfillmentMethod::from($customer['fulfillment']);
        $payment = PaymentMethod::from($customer['payment_method']);

        $order = DB::transaction(function () use ($lines, $customer, $user, $fulfillment, $payment) {
            $products = Product::query()->whereKey($lines->pluck('product.id')->unique())->lockForUpdate()->get()->keyBy('id');
            $variantIds = $lines->pluck('variant.id')->filter()->unique();
            $variants = ProductVariant::query()->whereKey($variantIds)->lockForUpdate()->get()->keyBy('id');

            $items = [];
            $errors = [];
            foreach ($lines as $line) {
                $product = $products->get($line['product']->id);
                $variant = $line['variant'] ? $variants->get($line['variant']->id) : null;
                $quantity = (int) $line['quantity'];
                $label = $product?->name.($variant ? ' — '.$variant->name : '');

                if (! $product || ! $product->isLive() || ($line['variant'] && (! $variant || ! $variant->is_active || $variant->product_id !== $product->id))) {
                    $errors[] = "{$label} sudah tidak tersedia.";

                    continue;
                }

                $stock = $variant ? $variant->stock : $product->stock;
                if ($stock !== null && $stock < $quantity) {
                    $errors[] = $stock > 0 ? "Stok {$label} tinggal {$stock}." : "Stok {$label} habis.";

                    continue;
                }
                if ($product->max_per_order && $quantity > $product->max_per_order) {
                    $errors[] = "{$label} maksimal {$product->max_per_order} per pesanan.";

                    continue;
                }

                $unitPrice = (int) ($variant?->price ?? $product->price);
                $items[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $unitPrice * $quantity,
                ];
            }

            if ($errors !== []) {
                throw ValidationException::withMessages(['cart' => $errors]);
            }

            $subtotal = array_sum(array_column($items, 'line_total'));
            $shippingFee = $fulfillment === FulfillmentMethod::Delivery ? $this->shippingFee($subtotal) : 0;

            $order = Order::create([
                'order_number' => $this->nextNumber(),
                'access_token' => Str::random(40),
                'user_id' => $user?->id,
                'profile_id' => $user?->profile?->id,
                'customer_name' => $customer['customer_name'],
                'whatsapp' => WhatsApp::normalize($customer['whatsapp']),
                'email' => $customer['email'] ?? null,
                'fulfillment' => $fulfillment,
                'shipping_address' => $fulfillment === FulfillmentMethod::Delivery ? ($customer['shipping_address'] ?? null) : null,
                'shipping_area' => $fulfillment === FulfillmentMethod::Delivery ? ($customer['shipping_area'] ?? null) : null,
                'notes' => $customer['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $subtotal + $shippingFee,
                'payment_method' => $payment,
                'status' => OrderStatus::PendingPayment,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_variant_id' => $item['variant']?->id,
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);

                $stocked = $item['variant'] ?? $item['product'];
                if ($stocked->stock !== null) {
                    $stocked->decrement('stock', $item['quantity']);
                }
            }

            return $order;
        });

        $this->notifier->toPermission('orders.manage', new TeamAlert(
            'order',
            "New order {$order->order_number}",
            "{$order->customer_name} ordered {$order->itemCount()} item(s) — ".Rupiah::format($order->total).' ('.$order->payment_method->label().').',
            route('admin.orders.show', $order),
        ));

        return $order;
    }

    public function shippingFee(int $subtotal): int
    {
        $freeFrom = $this->settings->integer('store_free_shipping_min');
        if ($freeFrom && $subtotal >= $freeFrom) {
            return 0;
        }

        return max(0, (int) $this->settings->integer('store_shipping_fee'));
    }

    public function uploadProof(Order $order, UploadedFile $file): Order
    {
        if (! $order->canUploadProof()) {
            throw ValidationException::withMessages(['proof' => 'Bukti pembayaran tidak bisa diunggah untuk pesanan ini.']);
        }

        $old = $order->payment_proof_path;
        $path = $file->storeAs("orders/{$order->id}", 'proof-'.Str::ulid().'.'.$file->guessExtension(), self::DISK);

        $order->update([
            'payment_proof_path' => $path,
            'proof_uploaded_at' => now(),
            'status' => OrderStatus::WaitingConfirmation,
        ]);
        if ($old && $old !== $path) {
            Storage::disk(self::DISK)->delete($old);
        }

        $this->notifier->toPermission('orders.manage', new TeamAlert(
            'order',
            "Payment proof for {$order->order_number}",
            "{$order->customer_name} uploaded a payment proof for ".Rupiah::format($order->total).'.',
            route('admin.orders.show', $order),
        ));

        return $order;
    }

    public function confirmPayment(Order $order, User $by): Order
    {
        $this->guardOpen($order);

        $order->update([
            'paid_at' => $order->paid_at ?? now(),
            'confirmed_by' => $by->id,
            'status' => in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::WaitingConfirmation], true) ? OrderStatus::Paid : $order->status,
        ]);
        $this->audit->log(AuditAction::Approve, $order, "Payment confirmed for order {$order->order_number}");
        $this->notifyCustomer($order, 'Pembayaran diterima', "Pembayaran untuk pesanan {$order->order_number} sudah kami terima. Pesananmu sedang kami siapkan.");

        return $order;
    }

    /** The proof could not be matched to a transfer: ask the customer to check and upload again. */
    public function rejectProof(Order $order, User $by, string $reason): Order
    {
        abort_unless($order->status === OrderStatus::WaitingConfirmation, 422);

        $order->update([
            'status' => OrderStatus::PendingPayment,
            'admin_notes' => trim(($order->admin_notes ? $order->admin_notes."\n" : '').now()->format('d/m H:i')." — Bukti ditolak ({$by->displayName()}): {$reason}"),
        ]);
        $this->notifyCustomer($order, 'Bukti pembayaran perlu dicek', "Bukti pembayaran untuk {$order->order_number} belum bisa kami konfirmasi: {$reason}");

        return $order;
    }

    public function updateStatus(Order $order, OrderStatus $status, User $by, ?string $trackingNumber = null): Order
    {
        if (! $this->canMove($order, $status)) {
            throw ValidationException::withMessages(['status' => "Pesanan tidak bisa dipindah dari “{$order->status->label()}” ke “{$status->label()}”."]);
        }

        $changes = ['status' => $status];
        if ($trackingNumber !== null) {
            $changes['tracking_number'] = $trackingNumber ?: null;
        }
        if ($status === OrderStatus::Paid || $status === OrderStatus::Completed) {
            $changes['paid_at'] = $order->paid_at ?? now();
            $changes['confirmed_by'] = $order->confirmed_by ?? $by->id;
        }
        if ($status === OrderStatus::Completed) {
            $changes['completed_at'] = now();
        }
        $order->update($changes);

        match ($status) {
            OrderStatus::Ready => $this->notifyCustomer($order, 'Pesanan siap diambil', "Pesanan {$order->order_number} sudah siap diambil. ".$this->settings->get('store_pickup_location')),
            OrderStatus::Shipped => $this->notifyCustomer($order, 'Pesanan dikirim', "Pesanan {$order->order_number} sudah dikirim.".($order->tracking_number ? " No. resi: {$order->tracking_number}" : '')),
            OrderStatus::Paid => $this->notifyCustomer($order, 'Pembayaran diterima', "Pembayaran untuk pesanan {$order->order_number} sudah kami terima."),
            default => null,
        };

        return $order;
    }

    public function canMove(Order $order, OrderStatus $status): bool
    {
        return in_array($status->value, self::TRANSITIONS[$order->status->value], true);
    }

    /** Cancel an order and return reserved stock. */
    public function cancel(Order $order, string $reason, ?User $by = null): Order
    {
        $this->guardOpen($order);

        DB::transaction(function () use ($order, $reason) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                $this->restock($item);
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => mb_substr($reason, 0, 250),
            ]);
        });

        if ($by) {
            $this->notifyCustomer($order, 'Pesanan dibatalkan', "Pesanan {$order->order_number} dibatalkan: {$reason}");
        } else {
            $this->notifier->toPermission('orders.manage', new TeamAlert('order', "Order {$order->order_number} cancelled", $reason, route('admin.orders.show', $order)));
        }

        return $order;
    }

    /** Cancel bank-transfer orders that were never paid within the configured window. */
    public function cancelExpired(): int
    {
        $hours = (int) $this->settings->integer('store_unpaid_cancel_hours');
        if ($hours <= 0) {
            return 0;
        }

        $expired = Order::query()
            ->where('status', OrderStatus::PendingPayment->value)
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->whereNull('paid_at')
            ->where('created_at', '<=', now()->subHours($hours))
            ->get();

        foreach ($expired as $order) {
            $this->cancel($order, "Dibatalkan otomatis: belum ada pembayaran dalam {$hours} jam.");
        }

        return $expired->count();
    }

    public function customerUrl(Order $order): string
    {
        return route('store.orders.show', [$order, $order->access_token]);
    }

    /** WhatsApp link from the store team to the customer, using the order template. */
    public function whatsappToCustomer(Order $order): ?string
    {
        return $this->whatsapp->templateLink($order->whatsapp, 'wa_template_order', [
            'name' => $order->customer_name,
            'order_number' => $order->order_number,
            'total' => Rupiah::format($order->total),
            'status' => $order->status->label(),
            'order_url' => $this->customerUrl($order),
        ]);
    }

    /** WhatsApp link from the customer to the store team about their order. */
    public function whatsappToStore(Order $order): ?string
    {
        $number = $this->settings->get('store_whatsapp') ?: $this->settings->get('contact_whatsapp');

        return $this->whatsapp->link($number, "Halo Every Nation Bekasi Store, saya {$order->customer_name} ingin menanyakan pesanan {$order->order_number} (".Rupiah::format($order->total).').');
    }

    private function restock(OrderItem $item): void
    {
        if ($item->product_variant_id) {
            ProductVariant::whereKey($item->product_variant_id)->whereNotNull('stock')->increment('stock', $item->quantity);

            return;
        }
        if ($item->product_id) {
            Product::withTrashed()->whereKey($item->product_id)->whereNotNull('stock')->increment('stock', $item->quantity);
        }
    }

    private function guardOpen(Order $order): void
    {
        if ($order->status->isFinal()) {
            throw ValidationException::withMessages(['status' => 'Pesanan ini sudah '.$order->status->label().'.']);
        }
    }

    private function notifyCustomer(Order $order, string $title, string $message): void
    {
        $this->notifier->toUser($order->user, new TeamAlert('order', $title, $message, $this->customerUrl($order)));
    }

    /** ENB-251005-0001: date + daily sequence. */
    private function nextNumber(): string
    {
        $prefix = 'ENB-'.now()->format('ymd').'-';
        $sequence = Order::where('order_number', 'like', $prefix.'%')->count() + 1;

        do {
            $number = $prefix.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
