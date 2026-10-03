{{-- $timeline: Collection<TimelineEntry> --}}
@if ($timeline->isEmpty())
    <x-empty title="No milestones yet" icon="flag" />
@else
    <ol class="relative space-y-6 border-l-2 border-line pl-6">
        @foreach ($timeline as $entry)
            <li class="relative">
                <span class="absolute top-1 -left-[1.95rem] size-3.5 rounded-full border-2 border-white bg-brand ring-2 ring-brand-100"></span>
                <p class="text-xs font-bold tracking-wider text-muted uppercase">{{ $entry->occurred_at->translatedFormat('j M Y') }}</p>
                <p class="font-bold text-ink">{{ $entry->title }}</p>
                @if ($entry->description)<p class="text-sm text-muted">{{ $entry->description }}</p>@endif
            </li>
        @endforeach
    </ol>
@endif
