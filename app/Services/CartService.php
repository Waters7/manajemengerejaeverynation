<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Session shopping cart. Only product / variant ids and quantities are stored;
 * names, prices and stock are always read fresh from the database.
 */
class CartService
{
    private const KEY = 'store.cart';

    public const MAX_QUANTITY = 50;

    public function __construct(private Session $session) {}

    public function add(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if (! $product->isLive()) {
            throw ValidationException::withMessages(['product' => 'Produk ini tidak tersedia.']);
        }

        if ($product->hasVariants() && (! $variant || ! $variant->is_active || $variant->product_id !== $product->id)) {
            throw ValidationException::withMessages(['variant_id' => 'Pilih varian terlebih dahulu.']);
        }
        if (! $product->hasVariants()) {
            $variant = null;
        }

        $key = $this->key($product->id, $variant?->id);
        $items = $this->raw();
        $wanted = ($items[$key]['quantity'] ?? 0) + max(1, $quantity);
        $allowed = $this->maximum($product, $variant);

        if ($allowed <= 0) {
            throw ValidationException::withMessages(['product' => 'Maaf, stok sedang habis.']);
        }

        $items[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => min($wanted, $allowed),
        ];
        $this->session->put(self::KEY, $items);
    }

    public function update(string $key, int $quantity): void
    {
        $items = $this->raw();
        if (! isset($items[$key])) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($key);

            return;
        }

        $items[$key]['quantity'] = min($quantity, self::MAX_QUANTITY);
        $this->session->put(self::KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = $this->raw();
        unset($items[$key]);
        $this->session->put(self::KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function count(): int
    {
        return (int) collect($this->raw())->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * Cart lines with current product data. Lines whose product is gone or unpublished are dropped.
     *
     * @return Collection<string, array{key: string, product: Product, variant: ?ProductVariant, quantity: int, unit_price: int, line_total: int, available: ?int, problem: ?string}>
     */
    public function lines(): Collection
    {
        $raw = collect($this->raw());
        if ($raw->isEmpty()) {
            return collect();
        }

        $products = Product::with(['activeVariants', 'images'])->findMany($raw->pluck('product_id')->unique())->keyBy('id');

        $lines = $raw->map(function (array $item, string $key) use ($products) {
            $product = $products->get($item['product_id']);
            if (! $product || ! $product->isLive()) {
                return null;
            }

            $variant = $item['variant_id'] ? $product->activeVariants->firstWhere('id', $item['variant_id']) : null;
            if ($item['variant_id'] && ! $variant) {
                return null;
            }
            if (! $item['variant_id'] && $product->activeVariants->isNotEmpty()) {
                return null;
            }

            $unitPrice = $variant ? ($variant->price ?? $product->price) : $product->price;
            $available = $variant ? $variant->stock : $product->stock;
            $quantity = (int) $item['quantity'];

            return [
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => (int) $unitPrice,
                'line_total' => (int) $unitPrice * $quantity,
                'available' => $available,
                'problem' => $this->problem($product, $quantity, $available),
            ];
        })->filter();

        if ($lines->count() !== $raw->count()) {
            $this->session->put(self::KEY, $raw->only($lines->keys()->all())->all());
        }

        return $lines;
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('line_total');
    }

    public function key(int $productId, ?int $variantId): string
    {
        return 'p'.$productId.'-v'.($variantId ?? 0);
    }

    private function problem(Product $product, int $quantity, ?int $available): ?string
    {
        if ($available !== null && $available <= 0) {
            return 'Stok habis';
        }
        if ($available !== null && $quantity > $available) {
            return "Stok tersisa {$available}";
        }
        if ($product->max_per_order && $quantity > $product->max_per_order) {
            return "Maksimal {$product->max_per_order} per pesanan";
        }

        return null;
    }

    private function maximum(Product $product, ?ProductVariant $variant): int
    {
        $limits = [self::MAX_QUANTITY];
        $stock = $variant ? $variant->stock : $product->stock;
        if ($stock !== null) {
            $limits[] = $stock;
        }
        if ($product->max_per_order) {
            $limits[] = $product->max_per_order;
        }

        return min($limits);
    }

    /** @return array<string, array{product_id: int, variant_id: ?int, quantity: int}> */
    private function raw(): array
    {
        return (array) $this->session->get(self::KEY, []);
    }
}
