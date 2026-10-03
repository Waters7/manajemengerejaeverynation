<x-layouts.admin title="Birthdays">
    <x-page-header title="Birthdays" description="Celebrate people — a simple message can mean a lot.">
        @if (auth()->user()->can('settings.manage'))
            <a href="{{ route('admin.settings.edit') }}#whatsapp" class="btn btn-outline btn-sm">Edit greeting template</a>
        @endif
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($ranges as $key => $label)
            <a href="{{ route('admin.birthdays.index', ['range' => $key]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => $range === $key])>{{ $label }}</a>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Birthday</th><th>Age</th><th>LifeGroup</th><th>WhatsApp</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><a href="{{ route('admin.members.show', $row['profile']) }}" class="flex items-center gap-3 font-bold hover:text-brand"><x-avatar :profile="$row['profile']" size="size-9" /> {{ $row['profile']->full_name }}</a></td>
                        <td>{{ $row['date']->isToday() ? 'Today 🎉' : $row['date']->translatedFormat('l, j F') }}</td>
                        <td>{{ $row['turning'] }}</td>
                        <td>{{ $row['lifegroup'] ?? '—' }}</td>
                        <td class="whitespace-nowrap">{{ \App\Services\WhatsApp::display($row['profile']->whatsapp) ?: '—' }}</td>
                        <td class="text-right"><x-wa-button :href="$row['link']" label="Send greeting" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No birthdays in this period" icon="cake" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
