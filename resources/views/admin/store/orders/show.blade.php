@use('App\Enums\FulfillmentMethod')
@use('App\Enums\OrderStatus')
<x-layouts.admin :title="'Order '.$order->order_number">
    <x-page-header :title="$order->order_number" :description="$order->created_at->translatedFormat('l, j F Y · H:i').' · '.$order->customer_name" :back="route('admin.orders.index')">
        <x-badge :value="$order->status" class="text-sm" />
        <x-wa-button :href="$whatsapp" label="WhatsApp customer" />
        <a href="{{ $customerUrl }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Customer page</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Items --}}
            <div class="card overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Item</th><th class="text-right">Price</th><th class="text-right">Qty</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <span class="font-bold">{{ $item->product_name }}</span>
                                    @if ($item->variant_name)<span class="text-muted"> — {{ $item->variant_name }}</span>@endif
                                    @if ($item->product && ! $item->product->trashed())
                                        <a href="{{ route('admin.store.products.edit', $item->product) }}" class="ml-1 text-xs font-semibold text-brand">product</a>
                                    @endif
                                </td>
                                <td class="text-right tabular-nums">@rupiah($item->unit_price)</td>
                                <td class="text-right tabular-nums">{{ $item->quantity }}</td>
                                <td class="text-right font-bold tabular-nums">@rupiah($item->line_total)</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-sm">
                        <tr><td colspan="3" class="text-right text-muted">Subtotal</td><td class="text-right font-bold tabular-nums">@rupiah($order->subtotal)</td></tr>
                        @if ($order->fulfillment === FulfillmentMethod::Delivery)
                            <tr><td colspan="3" class="text-right text-muted">Delivery</td><td class="text-right font-bold tabular-nums">@rupiah($order->shipping_fee)</td></tr>
                        @endif
                        <tr><td colspan="3" class="text-right text-base font-extrabold">Total</td><td class="text-right text-base font-extrabold text-brand tabular-nums">@rupiah($order->total)</td></tr>
                    </tfoot>
                </table>
            </div>

            {{-- Payment --}}
            <div class="card card-pad">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-extrabold uppercase">Payment</h2>
                        <p class="mt-1 text-sm text-muted">{{ $order->payment_method->label() }}</p>
                        @if ($order->isPaid())
                            <p class="mt-2 flex items-center gap-2 text-sm font-bold text-green-700"><x-icon name="check-circle" class="size-5" /> Paid {{ $order->paid_at->translatedFormat('j M Y · H:i') }}@if ($order->confirmer) · confirmed by {{ $order->confirmer->displayName() }}@endif</p>
                        @else
                            <p class="mt-2 text-sm font-bold text-amber-700">Not paid yet</p>
                        @endif
                    </div>
                    @if (! $order->isPaid() && ! $order->status->isFinal())
                        <form method="POST" action="{{ route('admin.orders.confirm', $order) }}" data-confirm="Confirm that {{ \App\Services\Rupiah::format($order->total) }} was received?">
                            @csrf
                            <button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Confirm payment received</button>
                        </form>
                    @endif
                </div>

                @if ($order->payment_proof_path)
                    <div class="mt-5 rounded-2xl border border-line p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm"><strong>Payment proof</strong> <span class="text-muted">uploaded {{ $order->proof_uploaded_at?->translatedFormat('j M Y · H:i') }}</span></p>
                            <a href="{{ route('admin.orders.proof', $order) }}" target="_blank" class="btn btn-outline btn-sm"><x-icon name="eye" class="size-4" /> Open proof</a>
                        </div>
                        @if (! str_ends_with($order->payment_proof_path, '.pdf'))
                            <a href="{{ route('admin.orders.proof', $order) }}" target="_blank"><img src="{{ route('admin.orders.proof', $order) }}" alt="Payment proof" class="mt-4 max-h-96 rounded-xl border border-line"></a>
                        @endif
                        @if ($order->status === OrderStatus::WaitingConfirmation)
                            <form method="POST" action="{{ route('admin.orders.reject-proof', $order) }}" class="mt-4 flex flex-wrap items-end gap-2" x-data="{ open: false }">
                                @csrf
                                <button type="button" class="btn btn-ghost btn-sm text-danger" x-show="! open" x-on:click="open = true">Proof doesn't match…</button>
                                <template x-if="open">
                                    <div class="flex w-full flex-wrap items-end gap-2">
                                        <x-form.input name="reason" label="Reason (sent to customer)" placeholder="e.g. Transfer belum masuk / nominal berbeda" required class="grow" />
                                        <button class="btn btn-danger btn-sm">Reject proof</button>
                                    </div>
                                </template>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Fulfilment --}}
            @unless ($order->status->isFinal())
                <div class="card card-pad">
                    <h2 class="font-extrabold uppercase">Next step</h2>
                    @if ($nextStatuses)
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4 flex flex-wrap items-end gap-3" x-data="{ status: '' }">
                            @csrf @method('PATCH')
                            <x-form.select name="status" label="Move order to" :options="$nextStatuses" placeholder="Choose…" x-model="status" required />
                            @if ($order->fulfillment === FulfillmentMethod::Delivery)
                                <div x-show="status === 'shipped'" x-cloak><x-form.input name="tracking_number" label="Tracking number" :value="$order->tracking_number" /></div>
                            @endif
                            <button class="btn btn-primary btn-sm">Update</button>
                        </form>
                        <p class="hint mt-2">Moving to “Selesai” also marks the order as paid (e.g. pay on pickup).</p>
                    @endif

                    <details class="mt-6 border-t border-line pt-4">
                        <summary class="cursor-pointer text-sm font-bold text-danger">Cancel order</summary>
                        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3 flex flex-wrap items-end gap-2" data-confirm="Cancel this order? Stock will be returned.">
                            @csrf
                            <x-form.input name="reason" label="Reason (sent to customer)" required class="grow" />
                            <button class="btn btn-danger btn-sm">Cancel order</button>
                        </form>
                    </details>
                </div>
            @endunless
            @if ($order->status === OrderStatus::Cancelled)
                <div class="card card-pad border-red-200">
                    <p class="font-extrabold text-red-700">Cancelled {{ $order->cancelled_at?->translatedFormat('j M Y · H:i') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $order->cancel_reason }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card card-pad space-y-3 text-sm">
                <h2 class="font-extrabold uppercase">Customer</h2>
                <p><span class="block text-xs text-muted">Name</span><strong>{{ $order->customer_name }}</strong></p>
                <p><span class="block text-xs text-muted">WhatsApp</span>{{ \App\Services\WhatsApp::display($order->whatsapp) }}</p>
                @if ($order->email)<p><span class="block text-xs text-muted">Email</span>{{ $order->email }}</p>@endif
                @if ($order->user)
                    <p><span class="block text-xs text-muted">Account</span>
                        @if ($order->profile_id && auth()->user()->can('members.view'))
                            <a href="{{ route('admin.members.show', $order->profile_id) }}" class="font-semibold text-brand">{{ $order->user->name }}</a>
                        @else
                            {{ $order->user->name }}
                        @endif
                    </p>
                @else
                    <p class="text-xs text-muted">Guest order (no account)</p>
                @endif
            </div>

            <div class="card card-pad space-y-3 text-sm">
                <h2 class="font-extrabold uppercase">{{ $order->fulfillment->label() }}</h2>
                @if ($order->fulfillment === FulfillmentMethod::Delivery)
                    <p class="whitespace-pre-line">{{ $order->shipping_address }}</p>
                    @if ($order->shipping_area)<p class="text-muted">{{ $order->shipping_area }}</p>@endif
                    @if ($order->tracking_number)<p>Tracking: <strong class="font-mono">{{ $order->tracking_number }}</strong></p>@endif
                @else
                    <p class="text-muted">Customer picks up at church.</p>
                @endif
                @if ($order->notes)<p class="rounded-xl bg-slate-50 p-3"><span class="block text-xs text-muted">Customer note</span>{{ $order->notes }}</p>@endif
            </div>

            <form method="POST" action="{{ route('admin.orders.notes', $order) }}" class="card card-pad space-y-3">
                @csrf @method('PATCH')
                <h2 class="font-extrabold uppercase">Internal notes</h2>
                <x-form.textarea name="admin_notes" :value="$order->admin_notes" rows="4" placeholder="Only visible to the store team" />
                @if ($order->fulfillment === FulfillmentMethod::Delivery)
                    <x-form.input name="tracking_number" label="Tracking number" :value="$order->tracking_number" />
                @endif
                <button class="btn btn-outline btn-sm">Save notes</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
