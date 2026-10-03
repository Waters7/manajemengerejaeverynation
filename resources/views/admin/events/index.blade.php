<x-layouts.admin title="Events">
    <x-page-header title="Events" description="Sunday Service, LifeGroup, discipleship, campus, prayer, training and special events.">
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New event</a>
    </x-page-header>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.events.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => ! $past])>Upcoming</a>
        <a href="{{ route('admin.events.index', ['past' => 1]) }}" @class(['chip', '!border-ink !bg-ink !text-white' => $past])>Past</a>
    </div>
    <x-filter-bar>
        @if ($past)<input type="hidden" name="past" value="1">@endif
        <x-form.select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="All" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Event</th><th>Date</th><th>Category</th><th>Registrations</th><th>Checked in</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td><a href="{{ route('admin.events.show', $event) }}" class="font-bold hover:text-brand">{{ $event->title }}</a>@if ($event->campus)<span class="block text-xs text-muted">{{ $event->campus->name }}</span>@endif</td>
                        <td class="whitespace-nowrap text-sm">{{ $event->starts_at->translatedFormat('D, j M Y · H:i') }}</td>
                        <td class="text-sm">{{ $event->category?->name ?? '—' }}</td>
                        <td class="text-sm">@if ($event->registration_enabled){{ $event->registered_count }}{{ $event->capacity ? ' / '.$event->capacity : '' }}@if ($event->waiting_count) <span class="text-amber-600">+{{ $event->waiting_count }} waiting</span>@endif @else — @endif</td>
                        <td class="text-sm">{{ $event->checked_in_count ?: '—' }}</td>
                        <td><x-badge :value="$event->status" /></td>
                        <td class="text-right whitespace-nowrap">
                            @if ($event->registration_enabled)<a href="{{ route('admin.events.check-in', $event) }}" class="btn btn-outline btn-sm"><x-icon name="qr" class="size-4" /> Check-in</a>@endif
                            <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-ghost btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No events here" icon="calendar" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
</x-layouts.admin>
