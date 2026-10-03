<x-layouts.admin title="Meetings & Attendance">
    <x-page-header title="Meetings & attendance" description="LifeGroup gatherings and who was there." />

    <x-filter-bar>
        <x-form.select name="lifegroup" label="LifeGroup" :options="$groups" :value="request('lifegroup')" placeholder="All my LifeGroups" />
        <x-form.input name="from" type="date" label="From" :value="request('from')" />
        <x-form.input name="to" type="date" label="To" :value="request('to')" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>LifeGroup</th><th>Topic</th><th>Present</th><th>Visitors</th><th></th></tr></thead>
            <tbody>
                @forelse ($meetings as $meeting)
                    @php $rate = $meeting->attendances_count ? round($meeting->present_count / $meeting->attendances_count * 100) : null; @endphp
                    <tr>
                        <td class="whitespace-nowrap font-bold">{{ $meeting->meeting_date->translatedFormat('D, j M Y') }}</td>
                        <td><a href="{{ route('admin.lifegroups.show', $meeting->lifeGroup) }}" class="hover:text-brand">{{ $meeting->lifeGroup->name }}</a></td>
                        <td>{{ $meeting->topic ?: '—' }}</td>
                        <td class="min-w-40"><x-progress :value="$meeting->present_count" :total="max(1, $meeting->attendances_count)" /></td>
                        <td>{{ $meeting->visitor_count }}</td>
                        <td class="text-right">@can('update', $meeting->lifeGroup)<a href="{{ route('admin.meetings.edit', $meeting) }}" class="btn btn-outline btn-sm">Edit</a>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No meetings recorded" icon="calendar" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $meetings->links() }}</div>
</x-layouts.admin>
