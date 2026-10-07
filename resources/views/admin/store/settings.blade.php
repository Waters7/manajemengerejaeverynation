<x-layouts.admin title="Store settings">
    <x-page-header title="Store settings" description="Payment details, pickup and delivery options shown to customers. Empty fields use the default shown as placeholder." />

    <form method="POST" action="{{ route('admin.store.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')
        @php
            $sections = [
                'Store' => ['store_enabled', 'store_intro'],
                'Payment' => ['store_bank_accounts', 'store_payment_instructions', 'store_pay_on_pickup', 'store_unpaid_cancel_hours'],
                'Pickup & delivery' => ['store_pickup_location', 'store_delivery_enabled', 'store_shipping_fee', 'store_free_shipping_min', 'store_delivery_note'],
                'WhatsApp' => ['store_whatsapp', 'wa_template_order'],
            ];
        @endphp
        @foreach ($sections as $heading => $keys)
            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">{{ $heading }}</h2>
                @if ($heading === 'Payment')
                    <p class="mt-1 text-sm text-muted">Bank accounts: one per line, e.g. <code>BCA 1234567890 a.n. Every Nation Bekasi</code>. Customers can copy each line.</p>
                @elseif ($heading === 'WhatsApp')
                    <p class="mt-1 text-sm text-muted">Placeholders: <code>{name}</code> <code>{order_number}</code> <code>{total}</code> <code>{status}</code> <code>{order_url}</code></p>
                @endif
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    @foreach ($keys as $key)
                        @php $definition = $definitions[$key]; $type = $definition['type'] ?? 'text'; @endphp
                        @if ($type === 'toggle')
                            <x-form.checkbox :name="$key" :label="$definition['label']" :checked="($stored[$key] ?? $definition['default']) === '1'" class="md:col-span-2" />
                        @elseif ($type === 'textarea')
                            <x-form.textarea :name="$key" :label="$definition['label']" :value="$stored[$key] ?? null" :placeholder="$definition['default']" :rows="$key === 'wa_template_order' ? 9 : 3" />
                        @elseif ($type === 'number')
                            <x-form.input :name="$key" type="number" min="0" :label="$definition['label']" :value="$stored[$key] ?? null" :placeholder="$definition['default']" />
                        @else
                            <x-form.input :name="$key" :label="$definition['label']" :value="$stored[$key] ?? null" :placeholder="$definition['default']" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
        <button class="btn btn-primary">Save store settings</button>
    </form>
</x-layouts.admin>
