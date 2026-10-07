<x-layouts.site title="Store" description="Buku pemuridan dan merchandise Every Nation Bekasi." :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Store', 'title' => "BOOKS &\nMERCHANDISE.", 'lead' => $settings->get('store_intro')])

    <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @unless ($settings->enabled('store_enabled'))
                <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                    <strong>Store sedang tutup sementara.</strong> Kamu tetap bisa melihat katalog; pemesanan akan dibuka kembali segera.
                </div>
            @endunless

            @if ($featured->isNotEmpty())
                <div class="mb-14">
                    <p class="eyebrow">Pilihan kami</p>
                    <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($featured as $product)
                            <a href="{{ route('store.show', $product->slug) }}" class="group card card-hover flex items-center gap-5 p-4">
                                <div class="aspect-[3/4] w-24 shrink-0 overflow-hidden rounded-xl bg-brand-50">
                                    @if ($product->coverUrl())
                                        <img src="{{ $product->coverUrl() }}" alt="" class="size-full object-cover transition group-hover:scale-105" loading="lazy">
                                    @else
                                        <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand to-brand-800"><img src="{{ asset('images/mark-white.png') }}" alt="" class="h-8 w-auto opacity-40"></div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[0.7rem] font-bold tracking-wider text-brand uppercase">{{ $product->type->label() }}</p>
                                    <p class="mt-1 leading-snug font-extrabold text-ink group-hover:text-brand">{{ $product->name }}</p>
                                    @if ($product->excerpt)<p class="mt-1 line-clamp-2 text-xs text-muted">{{ $product->excerpt }}</p>@endif
                                    <p class="mt-2 font-extrabold tabular-nums">@rupiah($product->priceRange()[0])</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('store.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => blank($filters['type'] ?? null) && blank($filters['category'] ?? null)])>Semua</a>
                    @foreach (\App\Enums\ProductType::cases() as $type)
                        <a href="{{ route('store.index', ['type' => $type->value]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => ($filters['type'] ?? null) === $type->value])>
                            <x-icon :name="$type === \App\Enums\ProductType::Book ? 'book' : 'tag'" class="size-4" /> {{ $type->label() }}
                        </a>
                    @endforeach
                    @foreach ($categories as $category)
                        <a href="{{ route('store.index', ['category' => $category->slug]) }}" @class(['chip', '!border-ink !bg-ink !text-white' => ($filters['category'] ?? null) === $category->slug])>{{ $category->name }}</a>
                    @endforeach
                </div>
                <form method="GET" action="{{ route('store.index') }}" class="relative w-full lg:w-72">
                    @if ($filters['type'] ?? null)<input type="hidden" name="type" value="{{ $filters['type'] }}">@endif
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari buku atau merchandise…" class="input rounded-full pl-10">
                </form>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                @forelse ($products as $product)
                    @include('site.partials.product-card', ['product' => $product])
                @empty
                    <div class="card col-span-2 md:col-span-3 lg:col-span-4"><x-empty title="Belum ada produk" icon="bag">Produk baru akan segera hadir.</x-empty></div>
                @endforelse
            </div>
            <div class="mt-10">{{ $products->links() }}</div>

            <div class="mt-16 grid gap-4 md:grid-cols-3">
                <div class="card card-pad flex gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand"><x-icon name="banknotes" class="size-5" /></span>
                    <div><p class="font-extrabold">Bayar mudah</p><p class="mt-1 text-sm text-muted">Transfer bank / QRIS, upload bukti di halaman pesanan.@if ($settings->enabled('store_pay_on_pickup')) Atau bayar saat ambil di gereja.@endif</p></div>
                </div>
                <div class="card card-pad flex gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand"><x-icon name="map-pin" class="size-5" /></span>
                    <div><p class="font-extrabold">Ambil di gereja</p><p class="mt-1 text-sm text-muted">{{ $settings->get('store_pickup_location') }}</p></div>
                </div>
                @if ($settings->enabled('store_delivery_enabled'))
                    <div class="card card-pad flex gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand"><x-icon name="truck" class="size-5" /></span>
                        <div><p class="font-extrabold">Bisa dikirim</p><p class="mt-1 text-sm text-muted">{{ $settings->get('store_delivery_note') }}</p></div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.site>
