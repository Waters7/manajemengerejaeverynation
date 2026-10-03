<x-layouts.admin title="Sermons">
    <x-page-header title="Sermons" description="Sunday messages with key points, reflection and YouTube / Spotify links.">
        <x-modal name="new-series" title="New sermon series">
            <x-slot:trigger><button type="button" class="btn btn-outline btn-sm">New series</button></x-slot:trigger>
            <form method="POST" action="{{ route('admin.sermon-series.store') }}" class="space-y-4">
                @csrf
                <x-form.input name="name" label="Series name" required />
                <x-form.textarea name="description" label="Description" rows="2" />
                <button class="btn btn-primary w-full">Create series</button>
            </form>
        </x-modal>
        <a href="{{ route('admin.sermons.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New sermon</a>
    </x-page-header>
    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-48 grow" />
        <x-form.select name="series" label="Series" :options="$series" :value="request('series')" placeholder="All" />
    </x-filter-bar>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Title</th><th>Series</th><th>Speaker</th><th>Media</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($sermons as $sermon)
                    <tr>
                        <td class="whitespace-nowrap text-sm">{{ $sermon->preached_on->translatedFormat('j M Y') }}</td>
                        <td class="font-bold">{{ $sermon->title }}<span class="block text-xs font-normal text-muted">{{ $sermon->bible_text }}</span></td>
                        <td class="text-sm">{{ $sermon->series?->name ?? '—' }}</td>
                        <td class="text-sm">{{ $sermon->speaker }}</td>
                        <td class="text-xs">{{ collect(['YouTube' => $sermon->youtube_url, 'Spotify' => $sermon->spotify_url])->filter()->keys()->implode(' · ') ?: '—' }}</td>
                        <td><x-badge :value="$sermon->status" /></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.sermons.edit', $sermon) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.sermons.destroy', $sermon) }}" class="inline" data-confirm="Delete this sermon?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No sermons yet" icon="play" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $sermons->links() }}</div>
</x-layouts.admin>
