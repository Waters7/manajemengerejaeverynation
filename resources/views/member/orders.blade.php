<x-layouts.member title="My Orders">
    <x-page-header title="My orders" description="Pesanan buku dan merchandise yang kamu buat saat login.">
        <a href="{{ route('store.index') }}" class="btn btn-primary btn-sm"><x-icon name="bag" class="size-4" /> Ke Store</a>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Pesanan</th><th class="text-right">Item</th><th class="text-right">Total</th><th>Pengambilan</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><span class="font-mono font-bold">{{ $order->order_number }}</span><span class="block text-xs text-muted">{{ $order->created_at->translatedFormat('j M Y · H:i') }}</span></td>
                        <td class="text-right tabular-nums">{{ (int) $order->item_count }}</td>
                        <td class="text-right font-bold whitespace-nowrap tabular-nums">@rupiah($order->total)</td>
                        <td class="text-sm">{{ $order->fulfillment->label() }}</td>
                        <td><x-badge :value="$order->status" /></td>
                        <td class="text-right"><a href="{{ route('store.orders.show', [$order, $order->access_token]) }}" class="btn btn-outline btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="Belum ada pesanan" icon="bag">Lihat buku pemuridan dan merchandise di <a href="{{ route('store.index') }}" class="font-semibold text-brand">Store</a>.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.member>
