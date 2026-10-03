<x-layouts.admin title="Settings">
    <x-page-header title="Settings" description="Church details and WhatsApp message templates. Empty fields use the default shown as placeholder." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')
        @foreach ($groups as $group => $definitions)
            <div class="card card-pad" id="{{ $group }}">
                <h2 class="font-extrabold uppercase">{{ $group === 'whatsapp' ? 'WhatsApp templates' : 'Church details' }}</h2>
                @if ($group === 'whatsapp')
                    <p class="mt-1 text-sm text-muted">Placeholders: <code>{nickname}</code> <code>{name}</code> <code>{followup_person}</code> <code>{interest}</code> <code>{sender}</code> <code>{lifegroup}</code> <code>{schedule}</code> <code>{invite_url}</code> <code>{ministry}</code></p>
                @endif
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    @foreach ($definitions as $key => $definition)
                        @if (($definition['type'] ?? 'text') === 'textarea')
                            <x-form.textarea :name="$key" :label="$definition['label']" :value="$stored[$key] ?? null" :placeholder="$definition['default']" :rows="$group === 'whatsapp' ? 9 : 3" />
                        @else
                            <x-form.input :name="$key" :label="$definition['label']" :value="$stored[$key] ?? null" :placeholder="$definition['default']" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
        <button class="btn btn-primary">Save settings</button>
    </form>
</x-layouts.admin>
