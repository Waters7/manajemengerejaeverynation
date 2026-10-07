<x-layouts.site title="Keranjang">
    <section class="bg-canvas py-10 sm:py-14">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('store.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> Lanjut belanja</a>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight uppercase sm:text-4xl">Keranjang</h1>

            @if ($errors->has('cart'))
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <p class="font-bold">Beberapa item perlu disesuaikan:</p>
                    <ul class="mt-1 list-disc pl-5">@foreach ($errors->get('cart') as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif

            @if ($lines->isEmpty())
                <div class="card mt-8"><x-empty title="Keranjang masih kosong" icon="bag">Yuk lihat buku dan merchandise di <a href="{{ route('store.index') }}" class="font-semibold text-brand">Store</a>.</x-empty></div>
            @else
                <div class="mt-8 grid gap-8 lg:grid-cols-3">
                    <div class="card divide-y divide-line lg:col-span-2">
                        @foreach ($lines as $line)
                            <div class="flex gap-4 p-4 sm:p-5">
                                <a href="{{ route('store.show', $line['product']->slug) }}" class="aspect-[3/4] w-20 shrink-0 overflow-hidden rounded-xl bg-brand-50">
                                    @if ($line['product']->coverUrl())
                                        <img src="{{ $line['product']->coverUrl() }}" alt="" class="size-full object-cover">
                                    @else
                                        <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand to-brand-800"><img src="{{ asset('images/mark-white.png') }}" alt="" class="h-6 w-auto opacity-40"></div>
                                    @endif
                                </a>
                                <div class="min-w-0 grow">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ route('store.show', $line['product']->slug) }}" class="font-extrabold text-ink hover:text-brand">{{ $line['product']->name }}</a>
                                            @if ($line['variant'])<p class="text-sm text-muted">{{ $line['variant']->name }}</p>@endif
                                            <p class="mt-1 text-sm text-muted tabular-nums">@rupiah($line['unit_price']) / pcs</p>
                                        </div>
                                        <p class="font-extrabold whitespace-nowrap tabular-nums">@rupiah($line['line_total'])</p>
                                    </div>
                                    @if ($line['problem'])
                                        <p class="mt-2 inline-flex rounded-lg bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">{{ $line['problem'] }}</p>
                                    @endif
                                    <div class="mt-3 flex items-center gap-3">
                                        <form method="POST" action="{{ route('store.cart.update', $line['key']) }}" class="inline-flex items-center gap-2" x-data>
                                            @csrf @method('PATCH')
                                            <label class="sr-only" for="qty-{{ $line['key'] }}">Jumlah</label>
                                            <input id="qty-{{ $line['key'] }}" type="number" name="quantity" value="{{ $line['quantity'] }}" min="0" max="{{ \App\Services\CartService::MAX_QUANTITY }}"
                                                class="input w-20 py-1.5 text-center tabular-nums" x-on:change="$el.form.submit()">
                                            <noscript><button class="btn btn-ghost btn-sm">Update</button></noscript>
                                        </form>
                                        <form method="POST" action="{{ route('store.cart.remove', $line['key']) }}">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-danger"><x-icon name="trash" class="size-4" /> Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <aside class="card h-fit p-6">
                        <h2 class="font-extrabold uppercase">Ringkasan</h2>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-muted">Subtotal ({{ $lines->sum('quantity') }} item)</dt><dd class="font-bold tabular-nums">@rupiah($subtotal)</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Ongkir</dt><dd class="text-muted">dihitung saat checkout</dd></div>
                        </dl>
                        @if ($hasProblems)
                            <p class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Sesuaikan jumlah item yang ditandai sebelum checkout.</p>
                        @elseif (! $settings->enabled('store_enabled'))
                            <p class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Store sedang tutup sementara.</p>
                        @else
                            <a href="{{ route('store.checkout') }}" class="btn btn-primary btn-lg mt-6 w-full">Checkout <x-icon name="arrow-right" class="size-5" /></a>
                        @endif
                    </aside>
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
