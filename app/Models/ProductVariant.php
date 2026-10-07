<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A size / colour option. Price falls back to the product price; NULL stock = untracked.
 */
class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'sku', 'price', 'stock', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer', 'is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sellingPrice(): int
    {
        return $this->price ?? (int) $this->product->price;
    }

    public function isSoldOut(): bool
    {
        return $this->stock !== null && $this->stock <= 0;
    }
}
