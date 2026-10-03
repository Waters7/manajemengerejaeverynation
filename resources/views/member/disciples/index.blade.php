<x-layouts.member title="My Disciples">
    <x-page-header title="My disciples" description="Orang-orang yang sedang kamu dampingi. Pemuridan adalah tentang relasi — bukan angka." />

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>LifeGroup</th><th>Current stage</th><th>Current program</th><th>Progress</th><th>Last meeting</th><th>Next follow-up</th><th></th></tr></thead>
            <tbody>
                @forelse ($relationships as $relationship)
                    @php
                        $disciple = $relationship->disciple;
                        $current = $progress->get($disciple->id)?->first();
                    @endphp
                    <tr>
                        <td><div class="flex items-center gap-3"><x-avatar :profile="$disciple" size="size-9" /><span class="font-bold">{{ $disciple->full_name }}</span></div></td>
                        <td class="text-sm">{{ $disciple->activeLifeGroups->first()?->name ?? '—' }}</td>
                        <td>{{ $disciple->currentStage?->name ?? '—' }}</td>
                        <td>{{ $disciple->currentProgram?->name ?? '—' }}</td>
                        <td class="min-w-36">@if ($current && $current->program->chapters->count())<x-progress :value="$current->completedUnits()" :total="$current->program->chapters->count()" />@else — @endif</td>
                        <td class="text-sm whitespace-nowrap">{{ $relationship->latestMeeting?->met_on->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="text-sm whitespace-nowrap">{{ $relationship->latestMeeting?->next_follow_up_at?->translatedFormat('j M Y') ?? $current?->next_follow_up_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td><a href="{{ route('member.disciples.show', $disciple) }}" class="btn btn-outline btn-sm">View journey</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty title="No disciples yet" icon="tree" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.member>
