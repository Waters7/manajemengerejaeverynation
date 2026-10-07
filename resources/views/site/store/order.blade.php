@use('App\Enums\FulfillmentMethod')
@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentMethod')
@php
    $steps = $order->fulfillment === FulfillmentMethod::Delivery
        ? ['Pesanan dibuat', 'Pembayaran', 'Diproses', 'Dikirim', 'Selesai']
        : ['Pesanan dibuat', 'Pembayaran', 'Diproses', 'Siap diambil', 'Selesai'];
    $reached = match ($order->status) {
        OrderStatus::PendingPayment, OrderStatus::WaitingConfirmation => $order->isPaid() ? 2 : 1,
        OrderStatus::Paid => 2,
        OrderStatus::Processing => 3,
        OrderStatus::Ready, OrderStatus::Shipped => 4,
        OrderStatus::Completed => 5,
        OrderStatus::Cancelled => 0,
    };
@endphp
<x-layouts.site :title="'Pesanan '.$order->order_number">
    <section class="bg-canvas py-10 sm:py-14">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Pesanan</p>
                    <h1 class="mt-1 font-mono text-3xl font-extrabold tracking-wide sm:text-4xl">{{ $order->order_number }}</h1>
                    <p class="mt-1 text-sm text-muted">{{ $order->created_at->translatedFormat('l, j F Y · H:i') }} · {{ $order->customer_name }}</p>
                </div>
                <x-badge :value="$order->status" class="text-sm" />
            </div>

            <p class="mt-4 rounded-xl border border-brand/20 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                <x-icon name="link" class="-mt-0.5 inline size-4" /> Simpan link halaman ini untuk mengecek status pesanan.
                @guest Atau <a href="{{ route('register') }}" class="font-bold underline">buat akun</a> agar pesanan berikutnya tersimpan di “My Orders”. @endguest
            </p>

            {{-- Progress --}}
            @if ($order->status === OrderStatus::Cancelled)
                <div class="card card-pad mt-6 border-red-200 bg-red-50/50">
                    <p class="font-extrabold text-red-700">Pesanan dibatalkan</p>
                    <p class="mt-1 text-sm text-red-700/80">{{ $order->cancel_reason }}</p>
                </div>
            @else
                <ol class="card mt-6 grid grid-cols-5 gap-2 p-5">
                    @foreach ($steps as $i => $step)
                        <li class="flex flex-col items-center gap-2 text-center">
                            <span @class(['grid size-9 place-items-center rounded-full text-sm font-bold', 'bg-brand text-white' => $i < $reached, 'bg-slate-100 text-slate-400' => $i >= $reached])>
                                @if ($i < $reached)<x-icon name="check" class="size-4" />@else{{ $i + 1 }}@endif
                            </span>
                            <span @class(['text-[0.7rem] leading-tight font-bold sm:text-xs', 'text-ink' => $i < $reached, 'text-muted' => $i >= $reached])>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="mt-6 grid gap-6 lg:grid-cols-5">
                <div class="space-y-6 lg:col-span-3">
                    {{-- Payment --}}
                    @if ($order->status !== OrderStatus::Cancelled && ! $order->isPaid())
                        <div class="card card-pad">
                            <h2 class="font-extrabold uppercase">Pembayaran</h2>
                            @if ($order->payment_method === PaymentMethod::PayOnPickup)
                                <p class="mt-2 text-sm text-slate-700">Bayar <strong>@rupiah($order->total)</strong> (tunai / QRIS) saat mengambil pesanan. Sebutkan nomor pesanan <strong class="font-mono">{{ $order->order_number }}</strong>.</p>
                            @else
                                <p class="mt-2 text-sm text-slate-700">Transfer tepat <strong class="text-lg text-brand tabular-nums">@rupiah($order->total)</strong> ke:</p>
                                @forelse ($bankAccounts as $account)
                                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-line bg-slate-50 px-4 py-3" x-data="{ copied: false }">
                                        <span class="font-mono text-sm font-bold text-ink">{{ $account }}</span>
                                        <button type="button" class="text-xs font-bold text-brand" x-on:click="navigator.clipboard.writeText(@js($account)); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Tersalin' : 'Salin'">Salin</button>
                                    </div>
                                @empty
                                    <p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Info rekening akan dikirim oleh tim kami via WhatsApp.</p>
                                @endforelse
                                <p class="mt-4 text-sm text-muted">{{ $settings->get('store_payment_instructions') }}</p>

                                @if ($order->canUploadProof())
                                    <form method="POST" action="{{ route('store.orders.proof', [$order, $token]) }}" enctype="multipart/form-data" class="mt-5 space-y-3 border-t border-line pt-5">
                                        @csrf
                                        @if ($order->payment_proof_path)
                                            <p class="flex items-center gap-2 text-sm font-semibold text-green-700"><x-icon name="check-circle" class="size-5" /> Bukti terkirim {{ $order->proof_uploaded_at?->diffForHumans() }} — menunggu konfirmasi.</p>
                                        @endif
                                        <label class="label" for="proof">{{ $order->payment_proof_path ? 'Ganti bukti pembayaran' : 'Upload bukti pembayaran' }}</label>
                                        <input id="proof" type="file" name="proof" accept="image/*,application/pdf" required class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-brand">
                                        @error('proof')<p class="error">{{ $message }}</p>@enderror
                                        <p class="hint">JPG, PNG, WebP atau PDF, maks. 5 MB.</p>
                                        <button class="btn btn-primary"><x-icon name="upload" class="size-4" /> Kirim bukti</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @elseif ($order->isPaid())
                        <div class="card card-pad flex items-center gap-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-green-50 text-green-600"><x-icon name="check-circle" class="size-6" /></span>
                            <div><p class="font-extrabold">Pembayaran diterima</p><p class="text-sm text-muted">{{ $order->paid_at->translatedFormat('j F Y · H:i') }}</p></div>
                        </div>
                    @endif

                    {{-- Fulfilment --}}
                    <div class="card card-pad">
                        <h2 class="font-extrabold uppercase">{{ $order->fulfillment->label() }}</h2>
                        @if ($order->fulfillment === FulfillmentMethod::Delivery)
                            <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $order->shipping_address }}@if ($order->shipping_area){{ "\n".$order->shipping_area }}@endif</p>
                            @if ($order->tracking_number)<p class="mt-3 text-sm">No. resi: <strong class="font-mono">{{ $order->tracking_number }}</strong></p>@endif
                        @else
                            <p class="mt-2 text-sm text-slate-700">{{ $settings->get('store_pickup_location') }}</p>
                            @if ($order->status === OrderStatus::Ready)
                                <p class="mt-3 rounded-xl bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">Pesananmu sudah siap diambil! Tunjukkan nomor pesanan ini di meja Store.</p>
                            @endif
                        @endif
                        @if ($order->notes)<p class="mt-3 text-sm text-muted">Catatan: {{ $order->notes }}</p>@endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-wa-button :href="$storeWhatsapp" label="Tanya via WhatsApp" size="" />
                        @if ($order->canBeCancelledByCustomer())
                            <form method="POST" action="{{ route('store.orders.cancel', [$order, $token]) }}" data-confirm="Batalkan pesanan ini?">
                                @csrf
                                <button class="btn btn-ghost text-danger">Batalkan pesanan</button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Items --}}
                <aside class="lg:col-span-2">
                    <div class="card p-6">
                        <h2 class="font-extrabold uppercase">Item</h2>
                        <ul class="mt-4 divide-y divide-line text-sm">
                            @foreach ($order->items as $item)
                                <li class="flex justify-between gap-3 py-2.5">
                                    <span class="min-w-0"><span class="font-semibold text-ink">{{ $item->product_name }}</span>@if ($item->variant_name) <span class="text-muted">— {{ $item->variant_name }}</span>@endif <span class="text-muted">× {{ $item->quantity }}</span></span>
                                    <span class="font-bold whitespace-nowrap tabular-nums">@rupiah($item->line_total)</span>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="mt-3 space-y-2 border-t border-line pt-4 text-sm">
                            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="font-bold tabular-nums">@rupiah($order->subtotal)</dd></div>
                            @if ($order->fulfillment === FulfillmentMethod::Delivery)
                                <div class="flex justify-between"><dt class="text-muted">Ongkir</dt><dd class="font-bold tabular-nums">{{ $order->shipping_fee ? \App\Services\Rupiah::format($order->shipping_fee) : 'Gratis' }}</dd></div>
                            @endif
                            <div class="flex justify-between border-t border-line pt-3 text-base"><dt class="font-extrabold">Total</dt><dd class="font-extrabold text-brand tabular-nums">@rupiah($order->total)</dd></div>
                        </dl>
                        <p class="mt-4 text-xs text-muted">{{ $order->payment_method->label() }}</p>
                    </div>
                    <a href="{{ route('store.index') }}" class="mt-4 block text-center text-sm font-bold text-brand">← Kembali ke Store</a>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.site>
