<?php

namespace App\Http\Controllers\Site;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * STORE: catalogue of books and merchandise.
 */
class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(ProductType::class)],
            'category' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $products = Product::published()
            ->with(['activeVariants', 'images' => fn ($q) => $q->limit(1), 'category'])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('author', 'like', "%{$term}%")))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('site.store.index', [
            'products' => $products,
            'categories' => ProductCategory::active()->whereHas('products', fn ($q) => $q->published())->get(),
            'featured' => blank($filters) && $request->integer('page', 1) === 1
                ? Product::published()->with(['activeVariants', 'images' => fn ($q) => $q->limit(1)])->where('is_featured', true)->orderBy('sort_order')->limit(3)->get()
                : collect(),
            'filters' => $filters,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->isLive(), 404);

        $product->load(['activeVariants', 'images', 'category', 'program']);

        return view('site.store.show', [
            'product' => $product,
            'related' => Product::published()
                ->with(['activeVariants', 'images' => fn ($q) => $q->limit(1)])
                ->whereKeyNot($product->id)
                ->where(fn ($q) => $q->where('product_category_id', $product->product_category_id)->orWhere('type', $product->type->value))
                ->inRandomOrder()
                ->limit(4)
                ->get(),
        ]);
    }
}
