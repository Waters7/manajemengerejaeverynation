<x-layouts.admin title="Devotionals">
    <x-page-header title="Devotionals" description="Daily devotionals for the church family.">
        <a href="{{ route('admin.devotionals.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New devotional</a>
    </x-page-header>
    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-48 grow" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="All" />
    </x-filter-bar>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Title</th><th>Reference</th><th>Author</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($devotionals as $devotional)
                    <tr>
                        <td class="whitespace-nowrap text-sm">{{ $devotional->devotional_date->translatedFormat('j M Y') }}</td>
                        <td class="font-bold">{{ $devotional->title }}</td>
                        <td class="text-sm">{{ $devotional->bible_reference }}</td>
                        <td class="text-sm">{{ $devotional->author }}</td>
                        <td><x-badge :value="$devotional->status" /></td>
                        <td class="text-right whitespace-nowrap">
                            @if ($devotional->isLive())<a href="{{ route('devotionals.show', $devotional->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" /></a>@endif
                            <a href="{{ route('admin.devotionals.edit', $devotional) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.devotionals.destroy', $devotional) }}" class="inline" data-confirm="Delete this devotional?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No devotionals yet" icon="book" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $devotionals->links() }}</div>
</x-layouts.admin>
