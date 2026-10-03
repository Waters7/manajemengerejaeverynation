<x-layouts.admin title="Discipleship Journey">
    <x-page-header title="Discipleship journey" description="Who is being discipled, where they are in the 4E journey, and who walks with them." />

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($funnel as $row)
            <a href="{{ route('admin.journey.index', ['stage' => $row['stage']->id]) }}" @class(['card card-hover p-4', 'ring-2 ring-brand' => (int) request('stage') === $row['stage']->id])>
                <span class="block h-1.5 w-10 rounded-full" style="background: {{ $row['stage']->color }}"></span>
                <p class="mt-3 text-3xl font-extrabold">{{ $row['count'] }}</p>
                <p class="text-xs font-bold tracking-wider text-muted uppercase">{{ $row['stage']->name }}</p>
            </a>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
        <span class="font-bold">Water baptism (members):</span>
        @foreach ($baptismStatuses as $value => $label)
            <a href="{{ route('admin.journey.index', ['baptism' => $value, 'status' => 'all']) }}" @class(['chip !py-1', '!border-brand !bg-brand !text-white' => request('baptism') === $value])>{{ $label }} <strong>{{ $baptismCounts[$value] ?? 0 }}</strong></a>
        @endforeach
    </div>

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-48 grow" />
        <x-form.select name="stage" label="Stage" :options="$stages" :value="request('stage')" placeholder="All" />
        <x-form.select name="program" label="Program" :options="$programs" :value="request('program')" placeholder="All" />
        <x-form.select name="discipler" label="Discipler" :options="$disciplers" :value="request('discipler')" placeholder="Anyone" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status', 'in_progress')" />
        <x-form.select name="baptism" label="Baptism" :options="$baptismStatuses" :value="request('baptism')" placeholder="All" />
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="quiet" value="1" class="checkbox" @checked(request('quiet'))> Needs encouragement (30+ days)</label>
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Stage</th><th>Program</th><th>Progress</th><th>Baptism</th><th>Discipler</th><th>Started</th><th>Last activity</th><th>Next follow-up</th></tr></thead>
            <tbody>
                @forelse ($progress as $row)
                    @php $total = $row->program->chapters->count() ?: (int) $row->program->total_sessions; @endphp
                    <tr>
                        <td><a href="{{ route('admin.progress.show', $row) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$row->profile" size="size-8" /> {{ $row->profile->full_name }}</a><span class="ml-10 block text-xs text-muted">{{ $row->profile->activeLifeGroups->first()?->name }}</span></td>
                        <td>{{ $row->program->stage?->name }}</td>
                        <td class="font-semibold">{{ $row->program->name }}</td>
                        <td class="min-w-36">
                            @if ($row->status === \App\Enums\ProgressStatus::Completed)
                                <x-badge :value="$row->status" />
                            @elseif ($row->program->chapters->count())
                                <x-progress :value="$row->completedUnits()" :total="$total" />
                            @else
                                <x-badge :value="$row->status" />
                            @endif
                        </td>
                        <td><x-badge :value="$row->profile->baptism_status" /></td>
                        <td class="text-sm">{{ $row->discipler?->displayName() ?? '—' }}</td>
                        <td class="whitespace-nowrap text-sm">{{ $row->started_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap text-sm text-muted">{{ $row->last_activity_at?->diffForHumans() ?? '—' }}</td>
                        <td @class(['whitespace-nowrap text-sm', 'font-bold text-amber-600' => $row->next_follow_up_at?->isPast()])>{{ $row->next_follow_up_at?->translatedFormat('j M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty title="No journeys match these filters" icon="sprout" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $progress->links() }}</div>
</x-layouts.admin>
