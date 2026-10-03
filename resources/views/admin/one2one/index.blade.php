<x-layouts.admin title="One 2 One">
    <x-page-header title="One 2 One" description="Personal discipleship — one person helping another follow Jesus, lesson by lesson.">
        @can('discipleship.manage')
            <x-modal name="start-one2one" title="Start One 2 One">
                <x-slot:trigger><button type="button" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Start One 2 One</button></x-slot:trigger>
                <form method="POST" action="{{ route('admin.one2one.store') }}" class="space-y-4">
                    @csrf
                    <x-form.select name="profile_id" label="Disciple" :options="$people" placeholder="Choose person…" required />
                    <x-form.select name="discipler_profile_id" label="Discipler" :options="$disciplers" placeholder="Choose discipler…" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input name="started_at" type="date" label="Start date" :value="today()" />
                        <x-form.input name="expected_completion_at" type="date" label="Expected completion" :value="today()->addWeeks(8)" />
                    </div>
                    <button class="btn btn-primary w-full">Start</button>
                </form>
            </x-modal>
        @endcan
    </x-page-header>

    @unless ($program)
        <div class="card mb-4 border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">The One 2 One program (slug <code>one-2-one</code>) is not configured in the curriculum.</div>
    @endunless

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-48 grow" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="Active" />
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="quiet" value="1" class="checkbox" @checked(request('quiet'))> To reconnect (14+ days quiet)</label>
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Disciple</th><th>Discipler</th><th>LifeGroup</th><th>Lessons</th><th>Start</th><th>Expected</th><th>Completed</th><th>Next follow-up</th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td><a href="{{ route('admin.progress.show', $record) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$record->profile" size="size-8" /> {{ $record->profile->full_name }}</a></td>
                        <td class="text-sm">{{ $record->discipler?->full_name ?? '—' }}</td>
                        <td class="text-sm">{{ $record->profile->activeLifeGroups->first()?->name ?? '—' }}</td>
                        <td class="min-w-36"><x-progress :value="$record->status === \App\Enums\ProgressStatus::Completed ? $record->program->chapters->count() : $record->completedUnits()" :total="$record->program->chapters->count()" /></td>
                        <td class="whitespace-nowrap text-sm">{{ $record->started_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap text-sm">{{ $record->expected_completion_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap text-sm">{{ $record->completed_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap text-sm">{{ $record->next_follow_up_at?->translatedFormat('j M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty title="No One 2 One here yet" icon="chat" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
</x-layouts.admin>
