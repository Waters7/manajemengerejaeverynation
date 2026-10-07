<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\DiscipleshipProgram;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * STORE → Products: books and merchandise with variants (size / colour), photos and stock.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.store.products.index', [
            'products' => Product::with(['category', 'variants', 'images' => fn ($q) => $q->limit(1)])
                ->withSum(['orderItems as sold' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))], 'quantity')
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
                ->when($request->filled('category'), fn ($q) => $q->where('product_category_id', $request->integer('category')))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->string('q').'%')->orWhere('sku', 'like', '%'.$request->string('q').'%')))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'types' => ProductType::options(),
            'categories' => ProductCategory::orderBy('sort_order')->pluck('name', 'id'),
            'statuses' => ContentStatus::options(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.store.products.form', $this->formData(new Product([
            'type' => ProductType::tryFrom((string) $request->query('type')) ?? ProductType::Book,
            'status' => ContentStatus::Draft,
        ])));
    }

    public function store(Request $request, MediaService $media): RedirectResponse
    {
        $product = DB::transaction(function () use ($request, $media) {
            $product = Product::create($this->validated($request, $media));
            $this->syncVariants($product, $request);

            return $product;
        });
        $this->storeImages($request, $product, $media);

        return redirect()->route('admin.store.products.edit', $product)->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.store.products.form', $this->formData($product->load(['variants', 'images'])));
    }

    public function update(Request $request, Product $product, MediaService $media): RedirectResponse
    {
        DB::transaction(function () use ($request, $product, $media) {
            $product->update($this->validated($request, $media, $product));
            $this->syncVariants($product, $request);
        });
        $this->storeImages($request, $product, $media);

        return back()->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.store.products.index')->with('status', 'Product removed from the store.');
    }

    public function destroyImage(ProductImage $image, MediaService $media): RedirectResponse
    {
        $media->delete($image->path);
        $image->delete();

        return back()->with('status', 'Photo removed.');
    }

    /**
     * Variants arrive as rows: existing ones keep their id (so order history stays linked),
     * rows without a name are ignored, and existing variants missing from the form are deactivated.
     */
    private function syncVariants(Product $product, Request $request): void
    {
        $rows = collect($request->input('variants', []))->filter(fn ($row) => filled($row['name'] ?? null))->values();
        $keep = [];

        foreach ($rows as $i => $row) {
            $values = [
                'name' => mb_substr($row['name'], 0, 120),
                'sku' => filled($row['sku'] ?? null) ? mb_substr($row['sku'], 0, 60) : null,
                'price' => filled($row['price'] ?? null) ? max(0, (int) $row['price']) : null,
                'stock' => filled($row['stock'] ?? null) ? (int) $row['stock'] : null,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => $i,
            ];

            $variant = filled($row['id'] ?? null) ? $product->variants()->find($row['id']) : null;
            if ($variant) {
                $variant->update($values);
            } else {
                $variant = $product->variants()->create($values);
            }
            $keep[] = $variant->id;
        }

        // Variants that were ordered stay (inactive) for order history; unused ones are deleted.
        $product->variants()->whereNotIn('id', $keep)->withCount('orderItems')->get()
            ->each(fn (ProductVariant $variant) => $variant->order_items_count > 0 ? $variant->update(['is_active' => false]) : $variant->delete());
    }

    private function storeImages(Request $request, Product $product, MediaService $media): void
    {
        $request->validate([
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'max:8192'],
        ]);

        $sequence = (int) $product->images()->max('sequence');
        foreach ($request->file('images', []) as $file) {
            $stored = $media->storeImage($file, 'store/'.$product->id, 1600, 640);
            $product->images()->create(['path' => $stored['path'], 'thumb_path' => $stored['thumb_path'], 'sequence' => ++$sequence]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, MediaService $media, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'product_category_id' => ['nullable', Rule::exists('product_categories', 'id')],
            'discipleship_program_id' => ['nullable', Rule::exists('discipleship_programs', 'id')],
            'author' => ['nullable', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:60'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'compare_at_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'stock' => ['nullable', 'integer', 'min:-1000', 'max:1000000'],
            'max_per_order' => ['nullable', 'integer', 'min:1', 'max:500'],
            'weight_grams' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'variants' => ['array', 'max:40'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['nullable', 'string', 'max:120'],
            'variants.*.sku' => ['nullable', 'string', 'max:60'],
            'variants.*.price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'variants.*.stock' => ['nullable', 'integer', 'min:-1000', 'max:1000000'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ]);
        $data['cover_path'] = $media->replace($product?->cover_path, $request->file('cover'), 'store', 1200);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_featured'] = $request->boolean('is_featured');

        return collect($data)->except(['cover', 'variants'])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'types' => ProductType::options(),
            'categories' => ProductCategory::orderBy('sort_order')->pluck('name', 'id'),
            'programs' => DiscipleshipProgram::orderBy('sequence')->pluck('name', 'id'),
            'statuses' => ContentStatus::options(),
        ];
    }
}
