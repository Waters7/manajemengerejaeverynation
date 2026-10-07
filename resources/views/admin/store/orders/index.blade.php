<x-layouts.admin title="Orders">
    <x-page-header title="Orders" description="Store orders — confirm payments, prepare items and hand them over or ship them.">
        <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Excel</a>
        <a href="{{ route('admin.orders.export', request()->query() + ['format' => 'csv']) }}" class="btn btn-ghost btn-sm">CSV</a>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Waiting for payment" :value="(int) ($counts['pending_payment'] ?? 0)" icon="clock" tone="amber" :href="route('admin.orders.index', ['status' => 'pending_payment'])" />
        <x-stat label="Proof to check" :value="(int) ($counts['waiting_confirmation'] ?? 0)" icon="banknotes" tone="rose" :href="route('admin.orders.index', ['status' => 'waiting_confirmation'])" />
        <x-stat label="To prepare / hand over" :value="(int) (($counts['paid'] ?? 0) + ($counts['processing'] ?? 0) + ($counts['ready'] ?? 0))" icon="bag" tone="brand" :href="route('admin.orders.index', ['status' => 'open'])" />
        <x-stat label="Paid this month" :value="\App\Services\Rupiah::format($revenue)" icon="chart" tone="green" />
    </div>

    <x-filter-bar>
        <x-form.input name="q" label="Search" :value="request('q')" placeholder="Order no., name, WhatsApp" />
        <x-form.select name="status" label="Status" :options="['open' => 'All open orders'] + $statuses" :value="request('status')" placeholder="All" />
        <x-form.select name="payment" label="Payment" :options="$payments" :value="request('payment')" placeholder="All" />
        <x-form.select name="fulfillment" label="Fulfilment" :options="$fulfillments" :value="request('fulfillment')" placeholder="All" />
        <x-form.input name="from" type="date" label="From" :value="request('from')" />
        <x-form.input name="to" type="date" label="To" :value="request('to')" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Order</th><th>Customer</th><th class="text-right">Items</th><th class="text-right">Total</th><th>Payment</th><th>Fulfilment</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold hover:text-brand">{{ $order->order_number }}</a>
                            <span class="block text-xs text-muted">{{ $order->created_at->translatedFormat('j M Y · H:i') }}</span>
                        </td>
                        <td>
                            <span class="font-semibold">{{ $order->customer_name }}</span>
                            <span class="block text-xs text-muted">{{ \App\Services\WhatsApp::display($order->whatsapp) }}</span>
                        </td>
                        <td class="text-right tabular-nums">{{ (int) $order->item_count }}</td>
                        <td class="text-right font-bold whitespace-nowrap tabular-nums">@rupiah($order->total)</td>
                        <td class="text-sm">{{ $order->payment_method->label() }}@if ($order->isPaid())<span class="block text-xs font-bold text-green-600">Paid</span>@endif</td>
                        <td class="text-sm">{{ $order->fulfillment->label() }}</td>
                        <td><x-badge :value="$order->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No orders found" icon="cart" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.admin>
