<x-layouts.admin title="Announcements">
    <x-page-header title="Announcements" description="Short messages on member and ministry dashboards.">
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New announcement</a>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Title</th><th>Audience</th><th>Status</th><th>Expires</th><th>By</th><th></th></tr></thead>
            <tbody>
                @forelse ($announcements as $announcement)
                    <tr>
                        <td class="font-bold">@if ($announcement->is_pinned)📌 @endif{{ $announcement->title }}<span class="block text-xs font-normal text-muted">{{ Str::limit($announcement->body, 80) }}</span></td>
                        <td><x-badge :value="$announcement->audience" color="blue" /></td>
                        <td><x-badge :value="$announcement->status" /></td>
                        <td class="text-sm">{{ $announcement->expires_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="text-sm">{{ $announcement->author?->name }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="inline" data-confirm="Delete this announcement?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No announcements" icon="megaphone" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</x-layouts.admin>
