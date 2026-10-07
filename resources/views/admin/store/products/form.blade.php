@php
    $variants = old('variants', $product->exists
        ? $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'price' => $v->price, 'stock' => $v->stock, 'is_active' => $v->is_active])->all()
        : []);
    $variants = array_values(array_map(fn ($v) => $v + ['id' => null, 'sku' => null, 'price' => null, 'stock' => null, 'is_active' => true], $variants));
    $title = $product->exists ? $product->name : 'New '.mb_strtolower($product->type?->label() ?? 'product');
@endphp
<x-layouts.admin :title="$title">
    <x-page-header :title="$title" :back="route('admin.store.products.index')">
        @if ($product->exists && $product->isLive())<a href="{{ route('store.show', $product->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Store page</a>@endif
    </x-page-header>

    <form method="POST" action="{{ $product->exists ? route('admin.store.products.update', $product) : route('admin.store.products.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad space-y-5" x-data="{ type: @js(old('type', $product->type?->value ?? 'book')) }">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="name" label="Name" :value="$product->name" required class="sm:col-span-2" />
                    <x-form.select name="type" label="Type" :options="$types" :value="$product->type" x-model="type" required />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="product_category_id" label="Category" :options="$categories" :value="$product->product_category_id" placeholder="—" hint="Manage categories under Store → Categories." />
                    <div x-show="type === 'book'"><x-form.input name="author" label="Author / publisher" :value="$product->author" /></div>
                </div>
                <x-form.input name="excerpt" label="Short description" :value="$product->excerpt" maxlength="300" hint="Shown under the title and on cards." />
                <x-form.textarea name="description" label="Full description" :value="$product->description" rows="7" hint="Plain text — line breaks are kept." />
                <div x-show="type === 'book'">
                    <x-form.select name="discipleship_program_id" label="Used in discipleship program" :options="$programs" :value="$product->discipleship_program_id" placeholder="—" hint="Optional — e.g. link the Purple Book to its program." />
                </div>
            </div>

            <div class="card card-pad space-y-5">
                <h2 class="font-extrabold uppercase">Price & stock</h2>
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="price" type="number" min="0" step="500" label="Price (Rp)" :value="$product->price" required />
                    <x-form.input name="compare_at_price" type="number" min="0" step="500" label="Normal price (Rp)" :value="$product->compare_at_price" hint="Shows a strike-through promo price." />
                    <x-form.input name="sku" label="SKU" :value="$product->sku" />
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="stock" type="number" label="Stock" :value="$product->stock" hint="Empty = not tracked. Ignored when variants are used." />
                    <x-form.input name="max_per_order" type="number" min="1" label="Max per order" :value="$product->max_per_order" />
                    <x-form.input name="weight_grams" type="number" min="0" label="Weight (gram)" :value="$product->weight_grams" />
                </div>
            </div>

            <div class="card card-pad" x-data="{ variants: @js($variants) }">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-extrabold uppercase">Variants</h2>
                        <p class="text-sm text-muted">Sizes, colours or editions — each with its own stock. Empty price = product price.</p>
                    </div>
                    <button type="button" x-on:click="variants.push({ id: null, name: '', sku: null, price: null, stock: null, is_active: true })" class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" /> Variant</button>
                </div>
                <div class="mt-4 flex flex-wrap gap-2" x-show="variants.length === 0">
                    <span class="text-xs font-bold text-muted uppercase">Quick add:</span>
                    @foreach (['S, M, L, XL' => ['S', 'M', 'L', 'XL'], 'S – XXL' => ['S', 'M', 'L', 'XL', 'XXL'], 'Hardcover / Paperback' => ['Paperback', 'Hardcover']] as $label => $names)
                        <button type="button" class="chip !px-3 !py-1 text-xs" x-on:click="@js($names).forEach(n => variants.push({ id: null, name: n, sku: null, price: null, stock: null, is_active: true }))">{{ $label }}</button>
                    @endforeach
                </div>
                <template x-if="variants.length">
                    <div class="mt-4 overflow-x-auto">
                        <table class="table">
                            <thead><tr><th>Name</th><th>SKU</th><th>Price (Rp)</th><th>Stock</th><th>Active</th><th></th></tr></thead>
                            <tbody>
                                <template x-for="(variant, i) in variants" :key="i">
                                    <tr>
                                        <td class="min-w-32">
                                            <input type="hidden" :name="'variants[' + i + '][id]'" :value="variant.id">
                                            <input class="input py-1.5" :name="'variants[' + i + '][name]'" x-model="variant.name" placeholder="e.g. M">
                                        </td>
                                        <td class="min-w-24"><input class="input py-1.5" :name="'variants[' + i + '][sku]'" x-model="variant.sku"></td>
                                        <td class="min-w-28"><input type="number" min="0" step="500" class="input py-1.5" :name="'variants[' + i + '][price]'" x-model="variant.price" placeholder="—"></td>
                                        <td class="min-w-20"><input type="number" class="input py-1.5" :name="'variants[' + i + '][stock]'" x-model="variant.stock" placeholder="∞"></td>
                                        <td>
                                            <input type="hidden" :name="'variants[' + i + '][is_active]'" :value="variant.is_active ? 1 : 0">
                                            <input type="checkbox" class="checkbox" x-model="variant.is_active">
                                        </td>
                                        <td class="text-right"><button type="button" x-on:click="variants.splice(i, 1)" class="btn btn-ghost btn-sm" aria-label="Remove"><x-icon name="x" class="size-4" /></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p class="hint mt-2">Variants that were already ordered are deactivated instead of deleted, so order history stays intact.</p>
                    </div>
                </template>
                @error('variants.*')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Photos</h2>
                @if ($product->exists && $product->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-6">
                        @foreach ($product->images as $image)
                            <div class="group relative overflow-hidden rounded-xl">
                                <img src="{{ $image->thumbUrl() }}" alt="" class="aspect-square w-full object-cover" loading="lazy">
                                <button type="submit" form="delete-image-{{ $image->id }}" class="absolute top-1.5 right-1.5 grid size-7 place-items-center rounded-full bg-white/90 text-danger opacity-0 shadow transition group-hover:opacity-100" aria-label="Remove photo"><x-icon name="trash" class="size-3.5" /></button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="images[]" accept="image/*" multiple class="input mt-4">
                <p class="hint">Extra photos for the product page — resized and converted to WebP.</p>
                @error('images.*')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-pad space-y-4">
                @include('admin.partials.publish-fields', ['model' => $product])
                <x-form.checkbox name="is_featured" label="Featured" :checked="$product->is_featured" hint="Highlighted at the top of the store." />
                <x-form.input name="sort_order" type="number" min="0" label="Sort order" :value="$product->sort_order" hint="Lower numbers first." />
            </div>
            <div class="card card-pad"><x-form.image name="cover" label="Cover photo" :current="$product->cover_path ? $product->coverUrl() : null" /></div>
            <button class="btn btn-primary w-full">{{ $product->exists ? 'Save product' : 'Create product' }}</button>
        </div>
    </form>

    @if ($product->exists)
        @foreach ($product->images as $image)
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.store.products.images.destroy', $image) }}" data-confirm="Remove this photo?" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endforeach
        <form method="POST" action="{{ route('admin.store.products.destroy', $product) }}" data-confirm="Remove this product from the store? Past orders keep their details." class="mt-6">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /> Delete product</button>
        </form>
    @endif
</x-layouts.admin>
