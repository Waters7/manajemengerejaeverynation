@php
    $images = collect();
    if ($product->cover_path) {
        $images->push(['full' => $product->coverUrl(), 'thumb' => $product->coverUrl()]);
    }
    foreach ($product->images as $image) {
        $images->push(['full' => $image->url(), 'thumb' => $image->thumbUrl()]);
    }
    $variants = $product->activeVariants->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->name,
        'price' => \App\Services\Rupiah::format($v->price ?? $product->price),
        'soldOut' => $v->isSoldOut(),
        'stock' => $v->stock,
    ])->values();
    $soldOut = $product->isSoldOut();
    $open = $settings->enabled('store_enabled');
    $firstAvailable = $variants->firstWhere('soldOut', false);
@endphp
<x-layouts.site :title="$product->name" :description="$product->excerpt">
    <section class="bg-canvas py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('store.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> Store</a>

            <div class="mt-6 grid gap-10 lg:grid-cols-2">
                {{-- Photos --}}
                <div x-data="{ active: 0, images: @js($images) }">
                    <div @class(['card overflow-hidden', 'aspect-[3/4] max-w-md mx-auto lg:mx-0' => $product->type === \App\Enums\ProductType::Book, 'aspect-square' => $product->type !== \App\Enums\ProductType::Book])>
                        <template x-if="images.length">
                            <img :src="images[active].full" alt="{{ $product->name }}" class="size-full object-cover">
                        </template>
                        <template x-if="! images.length">
                            <div class="flex size-full flex-col items-center justify-center gap-4 bg-gradient-to-br from-brand to-brand-800 p-10 text-center text-white">
                                <img src="{{ asset('images/mark-white.png') }}" alt="" class="h-20 w-auto opacity-40">
                                <p class="text-xl font-extrabold uppercase">{{ $product->name }}</p>
                            </div>
                        </template>
                    </div>
                    <template x-if="images.length > 1">
                        <div class="mt-4 flex gap-3 overflow-x-auto pb-1">
                            <template x-for="(image, index) in images" :key="index">
                                <button type="button" x-on:click="active = index" class="size-20 shrink-0 overflow-hidden rounded-xl border-2 transition"
                                    :class="active === index ? 'border-brand' : 'border-transparent opacity-70 hover:opacity-100'">
                                    <img :src="image.thumb" alt="" class="size-full object-cover">
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Details --}}
                <div x-data="{ variant: @js($firstAvailable['id'] ?? null), variants: @js($variants), qty: 1,
                        get current() { return this.variants.find(v => v.id === this.variant) } }">
                    <p class="eyebrow">{{ $product->category?->name ?? $product->type->label() }}</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $product->name }}</h1>
                    @if ($product->author)<p class="mt-2 text-muted">oleh <strong class="text-ink">{{ $product->author }}</strong></p>@endif

                    <div class="mt-5 flex items-baseline gap-3">
                        @if ($variants->isNotEmpty())
                            <span class="text-3xl font-extrabold tabular-nums" x-text="current ? current.price : '{{ \App\Services\Rupiah::format($product->priceRange()[0]) }}'">@rupiah($product->priceRange()[0])</span>
                        @else
                            <span class="text-3xl font-extrabold tabular-nums">@rupiah($product->price)</span>
                        @endif
                        @if ($product->isOnSale())
                            <span class="text-lg text-muted line-through tabular-nums">@rupiah($product->compare_at_price)</span>
                        @endif
                    </div>

                    @if ($product->excerpt)<p class="mt-5 text-lg text-slate-700">{{ $product->excerpt }}</p>@endif

                    <form method="POST" action="{{ route('store.cart.add') }}" class="card mt-8 space-y-5 p-5 sm:p-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        @if ($variants->isNotEmpty())
                            <div>
                                <p class="label">Pilih {{ $product->type === \App\Enums\ProductType::Merchandise ? 'ukuran / varian' : 'varian' }}</p>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach ($variants as $v)
                                        <label @class(['chip', 'cursor-not-allowed opacity-40 line-through' => $v['soldOut']])>
                                            <input type="radio" name="variant_id" value="{{ $v['id'] }}" class="sr-only" x-model.number="variant" @disabled($v['soldOut'])>
                                            {{ $v['name'] }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('variant_id')<p class="error">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        @if ($soldOut)
                            <p class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-600">Stok sedang habis. Hubungi kami jika ingin pre-order.</p>
                        @elseif (! $open)
                            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Store sedang tutup sementara.</p>
                        @else
                            <div class="flex flex-wrap items-end gap-4">
                                <div>
                                    <p class="label">Jumlah</p>
                                    <div class="inline-flex items-center rounded-full border border-line bg-white">
                                        <button type="button" class="p-2.5 text-slate-500 hover:text-brand" x-on:click="qty = Math.max(1, qty - 1)" aria-label="Kurangi"><x-icon name="minus" class="size-4" /></button>
                                        <input type="number" name="quantity" x-model.number="qty" min="1" max="{{ $product->max_per_order ?: \App\Services\CartService::MAX_QUANTITY }}" class="w-12 border-0 p-0 text-center font-bold tabular-nums focus:ring-0">
                                        <button type="button" class="p-2.5 text-slate-500 hover:text-brand" x-on:click="qty = Math.min({{ $product->max_per_order ?: \App\Services\CartService::MAX_QUANTITY }}, qty + 1)" aria-label="Tambah"><x-icon name="plus" class="size-4" /></button>
                                    </div>
                                </div>
                                <p class="pb-2 text-xs text-muted">
                                    @if ($variants->isNotEmpty())
                                        <span x-show="current && current.stock !== null && current.stock <= 10" x-text="current ? 'Sisa ' + current.stock + ' pcs' : ''"></span>
                                    @elseif ($product->stock !== null && $product->stock <= 10)
                                        Sisa {{ $product->stock }} pcs
                                    @endif
                                    @if ($product->max_per_order) · Maks. {{ $product->max_per_order }} per pesanan @endif
                                </p>
                            </div>
                            @error('product')<p class="error">{{ $message }}</p>@enderror
                            <div class="grid gap-3 sm:grid-cols-2">
                                <button class="btn btn-outline btn-lg" @if ($variants->isNotEmpty()) :disabled="! variant" @endif><x-icon name="bag" class="size-5" /> Tambah ke keranjang</button>
                                <button name="buy_now" value="1" class="btn btn-primary btn-lg" @if ($variants->isNotEmpty()) :disabled="! variant" @endif>Beli sekarang</button>
                            </div>
                        @endif
                    </form>

                    <ul class="mt-6 space-y-2 text-sm text-muted">
                        <li class="flex gap-2"><x-icon name="map-pin" class="size-4 shrink-0 text-brand" /> {{ $settings->get('store_pickup_location') }}</li>
                        @if ($settings->enabled('store_delivery_enabled'))
                            <li class="flex gap-2"><x-icon name="truck" class="size-4 shrink-0 text-brand" /> {{ $settings->get('store_delivery_note') }}</li>
                        @endif
                        @if ($product->program)
                            <li class="flex gap-2"><x-icon name="sprout" class="size-4 shrink-0 text-brand" /> Dipakai dalam perjalanan pemuridan: <a href="{{ route('discipleship') }}" class="font-semibold text-brand">{{ $product->program->name }}</a></li>
                        @endif
                    </ul>

                    @if ($product->description)
                        <div class="mt-8 border-t border-line pt-8">
                            <h2 class="text-lg font-extrabold uppercase">Deskripsi</h2>
                            <div class="mt-3 leading-relaxed whitespace-pre-line text-slate-700">{{ $product->description }}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="mt-20">
                    <h2 class="text-2xl font-extrabold tracking-tight uppercase">Kamu mungkin juga suka</h2>
                    <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                        @foreach ($related as $item)
                            @include('site.partials.product-card', ['product' => $item])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
