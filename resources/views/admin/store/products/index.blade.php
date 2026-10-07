<x-layouts.admin title="Products">
    <x-page-header title="Products" description="Books and merchandise in the church store. Empty stock = not tracked (pre-order / unlimited).">
        <a href="{{ route('store.index') }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> View store</a>
        <a href="{{ route('admin.store.products.create', ['type' => 'merchandise']) }}" class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" /> Merchandise</a>
        <a href="{{ route('admin.store.products.create', ['type' => 'book']) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Book</a>
    </x-page-header>

    <x-filter-bar>
        <x-form.input name="q" label="Search" :value="request('q')" placeholder="Name or SKU" />
        <x-form.select name="type" label="Type" :options="$types" :value="request('type')" placeholder="All" />
        <x-form.select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="All" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Product</th><th>Type</th><th class="text-right">Price</th><th class="text-right">Stock</th><th class="text-right">Sold</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        [$min, $max] = $product->priceRange();
                        $stock = $product->totalStock();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.store.products.edit', $product) }}" class="flex items-center gap-3">
                                <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                    @if ($product->coverUrl())<img src="{{ $product->coverUrl() }}" alt="" class="size-full object-cover" loading="lazy">@else<span class="grid size-full place-items-center"><x-icon :name="$product->type === \App\Enums\ProductType::Book ? 'book' : 'tag'" class="size-5 text-slate-300" /></span>@endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-bold hover:text-brand">{{ $product->name }}@if ($product->is_featured) <x-icon name="star" class="inline size-3.5 text-amber-500" />@endif</span>
                                    <span class="block text-xs text-muted">{{ $product->category?->name ?? 'Uncategorised' }}@if ($product->sku) · {{ $product->sku }}@endif @if ($product->variants->isNotEmpty()) · {{ $product->variants->where('is_active', true)->count() }} variants @endif</span>
                                </span>
                            </a>
                        </td>
                        <td><x-badge :value="$product->type" /></td>
                        <td class="text-right whitespace-nowrap tabular-nums">@rupiah($min)@if ($max > $min) – @rupiah($max)@endif</td>
                        <td class="text-right tabular-nums">
                            @if ($stock === null)
                                <span class="text-muted">∞</span>
                            @else
                                <span @class(['font-bold', 'text-danger' => $stock <= 0, 'text-amber-600' => $stock > 0 && $stock <= 5])>{{ $stock }}</span>
                            @endif
                        </td>
                        <td class="text-right tabular-nums">{{ (int) $product->sold }}</td>
                        <td><x-badge :value="$product->status" /></td>
                        <td class="text-right whitespace-nowrap">
                            @if ($product->isLive())<a href="{{ route('store.show', $product->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" /></a>@endif
                            <a href="{{ route('admin.store.products.edit', $product) }}" class="btn btn-ghost btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No products yet" icon="bag">Add your first book or merchandise item.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
</x-layouts.admin>
