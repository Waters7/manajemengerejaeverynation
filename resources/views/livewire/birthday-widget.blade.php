<div class="card">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5">
        <h2 class="flex items-center gap-2 font-extrabold uppercase"><x-icon name="cake" class="size-5 text-brand" /> Birthdays</h2>
        <div class="flex rounded-full bg-slate-100 p-1 text-xs font-bold">
            @foreach ($ranges as $key => $label)
                <button type="button" wire:click="setRange('{{ $key }}')" @class(['rounded-full px-3 py-1.5 transition', 'bg-white text-ink shadow-sm' => $range === $key, 'text-slate-500 hover:text-ink' => $range !== $key])>{{ $label }}</button>
            @endforeach
        </div>
    </div>
    <ul class="divide-y divide-line" wire:loading.class="opacity-50">
        @forelse ($rows as $row)
            <li class="flex items-center gap-3 px-5 py-3" wire:key="bday-{{ $row['profile']->id }}">
                <x-avatar :profile="$row['profile']" size="size-10" />
                <div class="min-w-0 grow">
                    <p class="truncate font-bold">{{ $row['profile']->full_name }}</p>
                    <p class="truncate text-xs text-muted">
                        {{ $row['date']->isToday() ? 'Today 🎉' : $row['date']->translatedFormat('l, j M') }} · turning {{ $row['turning'] }}
                        @if ($row['lifegroup']) · {{ $row['lifegroup'] }}@endif
                    </p>
                </div>
                <x-wa-button :href="$row['link']" label="Send greeting" />
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-muted">No birthdays in this period.</li>
        @endforelse
    </ul>
    @if ($total > $rows->count())
        <a href="{{ route('admin.birthdays.index', ['range' => $range]) }}" class="block border-t border-line px-5 py-3 text-center text-sm font-bold text-brand">See all {{ $total }} →</a>
    @endif
</div>
