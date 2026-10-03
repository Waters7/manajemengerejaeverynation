<x-layouts.admin title="Notifications">
    <x-page-header title="Notifications">
        <form method="POST" action="{{ route('admin.notifications.read') }}">@csrf<button class="btn btn-outline btn-sm"><x-icon name="check" class="size-4" /> Mark all read</button></form>
    </x-page-header>

    <div class="card divide-y divide-line">
        @forelse ($notifications as $notification)
            <a href="{{ $notification->data['url'] ?? '#' }}" @class(['flex items-start gap-3 p-4 hover:bg-slate-50', 'bg-brand-50/40' => ! $notification->read_at])>
                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-brand' => ! $notification->read_at, 'bg-transparent' => $notification->read_at])></span>
                <span class="grow">
                    <span class="block font-bold">{{ $notification->data['title'] ?? 'Notification' }}</span>
                    <span class="block text-sm text-muted">{{ $notification->data['message'] ?? '' }}</span>
                </span>
                <span class="shrink-0 text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <x-empty title="You're all caught up" icon="bell" />
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.admin>
