<x-layouts.admin title="Disciplers">
    <x-page-header title="Disciplers" description="People who are walking with others in discipleship.">
        <a href="{{ route('admin.disciplers.tree') }}" class="btn btn-primary btn-sm"><x-icon name="tree" class="size-4" /> Discipleship tree</a>
    </x-page-header>

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search discipler" :value="request('q')" class="min-w-56 grow" />
    </x-filter-bar>

    <div class="space-y-4">
        @forelse ($disciplers as $discipler)
            <div class="card" x-data="{ open: false }">
                <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between gap-4 p-5 text-left">
                    <span class="flex items-center gap-3">
                        <x-avatar :profile="$discipler" />
                        <span>
                            <span class="block font-extrabold">{{ $discipler->full_name }}</span>
                            <span class="block text-xs text-muted">{{ $discipler->currentStage?->name ?? '—' }} · {{ $discipler->activeLifeGroups->first()?->name ?? 'No LifeGroup' }}</span>
                        </span>
                    </span>
                    <span class="flex items-center gap-3">
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-extrabold text-brand">{{ $discipler->disciples_count }} {{ Str::plural('disciple', $discipler->disciples_count) }}</span>
                        <x-icon name="chevron-down" class="size-5 text-muted transition" x-bind:class="open && 'rotate-180'" />
                    </span>
                </button>
                <div x-show="open" x-cloak class="overflow-x-auto border-t border-line">
                    <table class="table">
                        <thead><tr><th>Disciple</th><th>Stage</th><th>Program</th><th>Last meeting</th><th>Next follow-up</th></tr></thead>
                        <tbody>
                            @foreach ($discipler->discipleRelationships as $relationship)
                                <tr>
                                    <td><a href="{{ route('admin.members.show', $relationship->disciple) }}#discipleship" class="font-bold hover:text-brand">{{ $relationship->disciple->full_name }}</a></td>
                                    <td>{{ $relationship->disciple->currentStage?->name ?? '—' }}</td>
                                    <td>{{ $relationship->disciple->currentProgram?->name ?? '—' }}</td>
                                    <td class="text-sm">{{ $relationship->latestMeeting?->met_on->translatedFormat('j M Y') ?? '—' }}</td>
                                    <td class="text-sm">{{ $relationship->latestMeeting?->next_follow_up_at?->translatedFormat('j M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-5 py-3"><a href="{{ route('admin.disciplers.tree', ['root' => $discipler->id]) }}" class="text-sm font-bold text-brand">View {{ $discipler->displayName() }}'s tree →</a></div>
                </div>
            </div>
        @empty
            <div class="card"><x-empty title="No disciplers yet" icon="tree" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $disciplers->links() }}</div>
</x-layouts.admin>
