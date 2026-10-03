<x-layouts.site :title="'Ticket — '.$event->title">
    <section class="bg-canvas py-16">
        <div class="mx-auto max-w-md px-4">
            <div class="card overflow-hidden text-center">
                <div class="bg-brand px-6 py-8 text-white">
                    <p class="text-xs font-bold tracking-[0.2em] text-white/70 uppercase">Event ticket</p>
                    <h1 class="mt-2 text-2xl font-extrabold">{{ $event->title }}</h1>
                    <p class="mt-2 text-sm text-white/80">{{ $event->starts_at->translatedFormat('l, j F Y · H:i') }} WIB</p>
                </div>
                <div class="p-8">
                    <x-badge :value="$registration->status" class="text-xs" />
                    <div class="mx-auto mt-6 w-56 [&>svg]:h-auto [&>svg]:w-full">{!! $qr !!}</div>
                    <p class="mt-4 font-mono text-lg font-bold tracking-[0.3em]">{{ $registration->code }}</p>
                    <p class="mt-4 font-extrabold">{{ $registration->name }}</p>
                    <p class="text-sm text-muted">{{ $event->location }}</p>
                    @if ($registration->checked_in_at)
                        <p class="mt-4 text-sm font-bold text-success">Checked in {{ $registration->checked_in_at->format('H:i') }}</p>
                    @endif
                    <p class="mt-6 text-xs text-muted">Tunjukkan QR code ini kepada tim usher saat check-in. Simpan halaman ini atau ambil screenshot.</p>
                </div>
            </div>
            <a href="{{ route('events.show', $event->slug) }}" class="mt-6 block text-center text-sm font-bold text-brand">← Back to event</a>
        </div>
    </section>
</x-layouts.site>
