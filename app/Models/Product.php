<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * A book or merchandise item in the church store. Prices are whole Rupiah;
 * a NULL stock means the item is not stock-tracked (pre-order / made to order).
 */
class Product extends Model
{
    use Auditable, HasSlug, Publishable, SoftDeletes;

    protected $fillable = [
        'product_category_id', 'discipleship_program_id', 'type', 'name', 'slug', 'sku', 'author', 'excerpt',
        'description', 'price', 'compare_at_price', 'stock', 'weight_grams', 'max_per_order', 'cover_path',
        'is_featured', 'status', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipProgram::class, 'discipleship_program_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sequence')->orderBy('id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }
        $first = $this->relationLoaded('images') ? $this->images->first() : null;

        return $first?->thumbUrl();
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('activeVariants')
            ? $this->activeVariants->isNotEmpty()
            : $this->activeVariants()->exists();
    }

    /**
     * Lowest and highest selling price across active variants (or the product price).
     *
     * @return array{0: int, 1: int}
     */
    public function priceRange(): array
    {
        $variants = $this->relationLoaded('activeVariants') ? $this->activeVariants : $this->activeVariants()->get();
        if ($variants->isEmpty()) {
            return [$this->price, $this->price];
        }
        $prices = $variants->map(fn (ProductVariant $v) => (int) ($v->price ?? $this->price));

        return [$prices->min(), $prices->max()];
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function isSoldOut(): bool
    {
        $variants = $this->relationLoaded('activeVariants') ? $this->activeVariants : $this->activeVariants()->get();
        if ($variants->isNotEmpty()) {
            return $variants->every(fn (ProductVariant $v) => $v->stock !== null && $v->stock <= 0);
        }

        return $this->stock !== null && $this->stock <= 0;
    }

    /** Total units in stock (null when any part is untracked). */
    public function totalStock(): ?int
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        if ($variants->isEmpty()) {
            return $this->stock;
        }

        return $variants->contains(fn (ProductVariant $v) => $v->stock === null) ? null : (int) $variants->sum('stock');
    }
}
