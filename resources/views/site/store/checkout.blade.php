<x-layouts.site title="Checkout">
    <section class="bg-canvas py-10 sm:py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('store.cart') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> Kembali ke keranjang</a>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight uppercase sm:text-4xl">Checkout</h1>

            @if ($errors->has('cart'))
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <ul class="list-disc pl-5">@foreach ($errors->get('cart') as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('store.checkout.store') }}" class="mt-8 grid gap-8 lg:grid-cols-5"
                x-data="{ fulfillment: @js(old('fulfillment', 'pickup')), payment: @js(old('payment_method', 'bank_transfer')),
                    subtotal: {{ $subtotal }}, fee: {{ $shippingFee }},
                    format(n) { return 'Rp' + n.toLocaleString('id-ID') } }"
                x-effect="if (fulfillment === 'delivery' && payment === 'pay_on_pickup') payment = 'bank_transfer'">
                @csrf
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                <div class="space-y-6 lg:col-span-3">
                    <div class="card card-pad space-y-5">
                        <h2 class="font-extrabold uppercase">Data pemesan</h2>
                        <x-form.input name="customer_name" label="Nama lengkap" :value="$user?->name" required />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="whatsapp" type="tel" label="WhatsApp" :value="$user?->whatsapp" placeholder="0812xxxxxxxx" hint="Untuk konfirmasi pesanan." required />
                            <x-form.input name="email" type="email" label="Email" :value="filter_var($user?->email, FILTER_VALIDATE_EMAIL) ? $user->email : null" />
                        </div>
                    </div>

                    <div class="card card-pad space-y-4">
                        <h2 class="font-extrabold uppercase">Pengambilan</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="choice">
                                <input type="radio" name="fulfillment" value="pickup" class="mt-0.5" x-model="fulfillment">
                                <span><span class="block">Ambil di gereja</span><span class="mt-0.5 block text-xs font-normal text-muted">{{ $settings->get('store_pickup_location') }}</span></span>
                            </label>
                            @if ($deliveryEnabled)
                                <label class="choice">
                                    <input type="radio" name="fulfillment" value="delivery" class="mt-0.5" x-model="fulfillment">
                                    <span><span class="block">Kirim ke alamat</span><span class="mt-0.5 block text-xs font-normal text-muted">Ongkir {{ $shippingFee ? \App\Services\Rupiah::format($shippingFee) : 'gratis' }}. {{ $settings->get('store_delivery_note') }}</span></span>
                                </label>
                            @endif
                        </div>
                        @error('fulfillment')<p class="error">{{ $message }}</p>@enderror
                        @if ($deliveryEnabled)
                            <div x-show="fulfillment === 'delivery'" x-cloak class="space-y-5 pt-2">
                                <x-form.textarea name="shipping_address" label="Alamat lengkap" rows="3" placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kode pos" />
                                <x-form.input name="shipping_area" label="Kota / area" placeholder="mis. Bekasi Barat" />
                            </div>
                        @endif
                    </div>

                    <div class="card card-pad space-y-4">
                        <h2 class="font-extrabold uppercase">Pembayaran</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="choice">
                                <input type="radio" name="payment_method" value="bank_transfer" class="mt-0.5" x-model="payment">
                                <span><span class="block">Transfer bank / QRIS</span><span class="mt-0.5 block text-xs font-normal text-muted">Info rekening tampil setelah pesanan dibuat.</span></span>
                            </label>
                            @if ($payOnPickup)
                                <label class="choice" :class="fulfillment === 'delivery' && 'opacity-40 pointer-events-none'">
                                    <input type="radio" name="payment_method" value="pay_on_pickup" class="mt-0.5" x-model="payment" :disabled="fulfillment === 'delivery'">
                                    <span><span class="block">Bayar saat ambil</span><span class="mt-0.5 block text-xs font-normal text-muted">Tunai / QRIS di meja Store.</span></span>
                                </label>
                            @endif
                        </div>
                        @error('payment_method')<p class="error">{{ $message }}</p>@enderror
                        <x-form.textarea name="notes" label="Catatan (opsional)" rows="2" placeholder="mis. nama untuk diambilkan, waktu ambil" />
                    </div>
                </div>

                <aside class="lg:col-span-2">
                    <div class="card sticky top-24 p-6">
                        <h2 class="font-extrabold uppercase">Pesanan kamu</h2>
                        <ul class="mt-4 divide-y divide-line text-sm">
                            @foreach ($lines as $line)
                                <li class="flex justify-between gap-3 py-2.5">
                                    <span class="min-w-0"><span class="font-semibold text-ink">{{ $line['product']->name }}</span>@if ($line['variant']) <span class="text-muted">— {{ $line['variant']->name }}</span>@endif <span class="text-muted">× {{ $line['quantity'] }}</span></span>
                                    <span class="font-bold whitespace-nowrap tabular-nums">@rupiah($line['line_total'])</span>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="mt-3 space-y-2 border-t border-line pt-4 text-sm">
                            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="font-bold tabular-nums">@rupiah($subtotal)</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Ongkir</dt><dd class="font-bold tabular-nums" x-text="fulfillment === 'delivery' ? (fee ? format(fee) : 'Gratis') : '—'">—</dd></div>
                            <div class="flex justify-between border-t border-line pt-3 text-base"><dt class="font-extrabold">Total</dt><dd class="font-extrabold text-brand tabular-nums" x-text="format(subtotal + (fulfillment === 'delivery' ? fee : 0))">@rupiah($subtotal)</dd></div>
                        </dl>
                        <button class="btn btn-primary btn-lg mt-6 w-full">Buat pesanan</button>
                        <p class="mt-3 text-center text-xs text-muted">Harga & stok dicek ulang saat pesanan dibuat.</p>
                    </div>
                </aside>
            </form>
        </div>
    </section>
</x-layouts.site>
