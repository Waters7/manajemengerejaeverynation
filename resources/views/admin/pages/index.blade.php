<x-layouts.admin title="Pages">
    <x-page-header title="Pages" description="CMS pages. Create a page with slug “about” or “discipleship” to replace the default text of those pages.">
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New page</a>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Title</th><th>URL</th><th>Status</th><th>Updated</th><th></th></tr></thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td class="font-bold">{{ $page->title }}</td>
                        <td class="font-mono text-xs">/pages/{{ $page->slug }}</td>
                        <td><x-badge :value="$page->status" /></td>
                        <td class="text-sm text-muted">{{ $page->updated_at->diffForHumans() }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if ($page->isLive())<a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" /></a>@endif
                            <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline" data-confirm="Delete this page?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty title="No pages yet" icon="document" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pages->links() }}</div>
</x-layouts.admin>
