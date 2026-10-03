<div class="mx-auto max-w-2xl space-y-6">
    <div class="card card-pad text-center">
        <p class="eyebrow">Checked in</p>
        <p class="mt-2 text-5xl font-extrabold tabular-nums">{{ $checkedIn }}<span class="text-2xl text-muted"> / {{ $total }}</span></p>
        <div class="mx-auto mt-4 max-w-sm"><x-progress :value="$checkedIn" :total="max(1, $total)" :label="false" /></div>
    </div>

    <form wire:submit="checkInCode" class="card card-pad">
        <label class="label" for="code">Scan QR / type ticket code</label>
        <div class="flex gap-2">
            <input id="code" wire:model="code" class="input font-mono text-lg tracking-[0.3em] uppercase" placeholder="ABC123XYZ0" autofocus autocomplete="off">
            <button class="btn btn-primary">Check in</button>
        </div>
        <p class="hint">USB / phone QR scanners type the code and press Enter automatically.</p>
    </form>

    @if ($message)
        <div @class(['rounded-2xl p-5 text-center text-lg font-bold', 'bg-green-50 text-green-700' => $success, 'bg-red-50 text-red-700' => ! $success])>{{ $message }}</div>
    @endif

    <div class="card">
        <div class="border-b border-line p-5">
            <label class="label" for="search">Find by name or WhatsApp</label>
            <input id="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Type at least 2 characters">
        </div>
        <ul class="divide-y divide-line">
            @foreach ($results as $registration)
                <li class="flex items-center justify-between gap-3 px-5 py-3" wire:key="reg-{{ $registration->id }}">
                    <span><span class="block font-bold">{{ $registration->name }}</span><span class="text-xs text-muted">{{ \App\Services\WhatsApp::display($registration->whatsapp) }} · {{ $registration->status->label() }}</span></span>
                    @if ($registration->checked_in_at)
                        <x-badge color="green">In {{ $registration->checked_in_at->format('H:i') }}</x-badge>
                    @else
                        <button type="button" wire:click="checkIn({{ $registration->id }})" class="btn btn-success btn-sm">Check in</button>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
