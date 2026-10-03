<div class="card overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-line p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="eyebrow">{{ $progress->program->type->label() }}</p>
            <h3 class="text-lg font-extrabold">{{ $progress->program->name }}</h3>
            <p class="text-xs text-muted">
                Started {{ $progress->started_at?->translatedFormat('j M Y') ?? '—' }}
                @if ($progress->discipler) · Discipler: {{ $progress->discipler->displayName() }}@endif
                @if ($progress->expected_completion_at) · Target {{ $progress->expected_completion_at->translatedFormat('j M Y') }}@endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <x-badge :value="$progress->status" />
            <span class="text-2xl font-extrabold tabular-nums text-brand">{{ $progress->completedUnits() }}<span class="text-base text-muted"> / {{ $chapters->count() }}</span></span>
        </div>
    </div>

    @if ($chapters->isEmpty())
        <p class="p-5 text-sm text-muted">This program has no chapters/lessons configured. Track it through class attendance instead.</p>
    @else
        <ul class="divide-y divide-line">
            @foreach ($chapters as $chapter)
                @php $row = $rows->get($chapter->id); $done = $row?->status === \App\Enums\ProgressStatus::Completed; @endphp
                <li class="p-4 sm:px-5" wire:key="chapter-{{ $chapter->id }}">
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="toggle({{ $chapter->id }})" wire:loading.attr="disabled"
                            @class(['grid size-7 shrink-0 place-items-center rounded-full border-2 transition', 'border-success bg-success text-white' => $done, 'border-slate-300 hover:border-brand' => ! $done])
                            aria-label="Toggle {{ $chapter->title }}">
                            @if ($done)<x-icon name="check" class="size-4" />@endif
                        </button>
                        <div class="min-w-0 grow">
                            <p @class(['text-sm font-bold', 'text-ink' => $done, 'text-slate-600' => ! $done])>{{ $chapter->number }}. {{ $chapter->title }}</p>
                            @if ($row?->completed_on)<p class="text-xs text-muted">Completed {{ $row->completed_on->translatedFormat('j M Y') }}</p>@endif
                        </div>
                        <button type="button" wire:click="$set('editing', {{ $editing === $chapter->id ? 'null' : $chapter->id }})" class="text-xs font-bold text-brand">
                            {{ filled($notes[$chapter->id] ?? null) ? 'Notes' : 'Add note' }}
                        </button>
                    </div>
                    @if ($editing === $chapter->id)
                        <div class="mt-3 pl-10">
                            <textarea wire:model="notes.{{ $chapter->id }}" rows="2" class="input" placeholder="Catatan pertemuan…"></textarea>
                            <button type="button" wire:click="saveNote({{ $chapter->id }})" class="btn btn-dark btn-sm mt-2">Save note</button>
                        </div>
                    @elseif (filled($notes[$chapter->id] ?? null))
                        <p class="mt-1 pl-10 text-xs whitespace-pre-line text-muted">{{ $notes[$chapter->id] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="flex flex-wrap items-end gap-3 border-t border-line bg-slate-50/60 p-4 sm:px-5">
        <label class="grow sm:grow-0">
            <span class="label">Next follow-up</span>
            <input type="date" wire:model="nextFollowUp" class="input">
        </label>
        <button type="button" wire:click="saveFollowUp" class="btn btn-outline btn-sm">Save</button>
        <span x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" x-cloak class="text-xs font-bold text-success">Saved ✓</span>
    </div>
</div>
