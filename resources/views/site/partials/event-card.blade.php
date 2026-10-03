<a href="{{ route('events.show', $event->slug) }}" class="group card card-hover flex flex-col overflow-hidden">
    <div class="relative aspect-[16/10] overflow-hidden bg-brand-50">
        @if ($event->coverUrl())
            <img src="{{ $event->coverUrl() }}" alt="" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand to-brand-800">
                <img src="{{ asset('images/mark-white.png') }}" alt="" class="h-1/3 w-auto opacity-25">
            </div>
        @endif
        <div class="absolute top-4 left-4 rounded-2xl bg-white px-3 py-2 text-center shadow-sm">
            <p class="text-[0.65rem] font-bold tracking-widest text-brand uppercase">{{ $event->starts_at->translatedFormat('M') }}</p>
            <p class="text-xl leading-none font-extrabold text-ink">{{ $event->starts_at->format('d') }}</p>
        </div>
    </div>
    <div class="flex grow flex-col p-5">
        @if ($event->category)
            <p class="text-xs font-bold tracking-wider uppercase" style="color: {{ $event->category->color }}">{{ $event->category->name }}</p>
        @endif
        <h3 class="mt-1 text-lg leading-snug font-extrabold text-ink group-hover:text-brand">{{ $event->title }}</h3>
        <div class="mt-auto space-y-1 pt-4 text-sm text-muted">
            <p class="flex items-center gap-2"><x-icon name="clock" class="size-4" /> {{ $event->starts_at->translatedFormat('l, j F Y · H:i') }}</p>
            @if ($event->location)
                <p class="flex items-center gap-2"><x-icon name="map-pin" class="size-4" /> {{ $event->location }}</p>
            @endif
        </div>
    </div>
</a>
