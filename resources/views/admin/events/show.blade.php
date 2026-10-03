<x-layouts.admin :title="$event->title">
    <x-page-header :title="$event->title" :eyebrow="($event->category?->name ?? 'Event').' · '.$event->starts_at->translatedFormat('l, j F Y · H:i')" :description="$event->location" :back="route('admin.events.index')">
        <x-badge :value="$event->status" />
        @if ($event->isLive())<a href="{{ route('events.show', $event->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Public page</a>@endif
        <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
        @if ($event->registration_enabled)
            <a href="{{ route('admin.events.check-in', $event) }}" class="btn btn-primary btn-sm"><x-icon name="qr" class="size-4" /> Check-in</a>
        @endif
    </x-page-header>

    @if ($event->registration_enabled)
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Registered" :value="($counts['registered'] ?? 0).($event->capacity ? ' / '.$event->capacity : '')" icon="users" />
            <x-stat label="Waiting list" :value="$counts['waiting_list'] ?? 0" icon="clock" tone="amber" />
            <x-stat label="Checked in" :value="$checkedIn" icon="check-circle" tone="green" />
            <x-stat label="Cancelled" :value="$counts['cancelled'] ?? 0" icon="x" tone="slate" />
        </div>

        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5">
                <h2 class="font-extrabold uppercase">Participants</h2>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.events.show', $event) }}" @class(['chip !py-1', '!border-brand !bg-brand !text-white' => ! request('status')])>All</a>
                    @foreach ($statuses as $value => $label)
                        <a href="{{ route('admin.events.show', [$event, 'status' => $value]) }}" @class(['chip !py-1', '!border-brand !bg-brand !text-white' => request('status') === $value])>{{ $label }}</a>
                    @endforeach
                    @can('reports.export')
                        <a href="{{ route('admin.events.export', $event) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Excel</a>
                    @endcan
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Name</th><th>WhatsApp</th><th>Registered</th><th>Code</th><th>Status</th><th>Check-in</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($registrations as $registration)
                            <tr>
                                <td class="font-bold">@if ($registration->profile)<a href="{{ route('admin.members.show', $registration->profile) }}" class="hover:text-brand">{{ $registration->name }}</a>@else{{ $registration->name }}@endif</td>
                                <td class="text-sm whitespace-nowrap"><a href="{{ $whatsApp->link($registration->whatsapp) }}" target="_blank" class="hover:text-brand">{{ \App\Services\WhatsApp::display($registration->whatsapp) }}</a></td>
                                <td class="text-sm whitespace-nowrap">{{ $registration->created_at->translatedFormat('j M H:i') }}</td>
                                <td class="font-mono text-xs">{{ $registration->code }}</td>
                                <td><x-badge :value="$registration->status" /></td>
                                <td class="text-sm">{{ $registration->checked_in_at?->format('H:i') ?? '—' }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @foreach (array_filter([
                                        ! $registration->checked_in_at && $registration->status !== \App\Enums\RegistrationStatus::Cancelled ? ['check_in', 'Check in', 'btn-success'] : null,
                                        $registration->checked_in_at ? ['undo_check_in', 'Undo', 'btn-ghost'] : null,
                                        $registration->status === \App\Enums\RegistrationStatus::WaitingList ? ['confirm', 'Confirm seat', 'btn-outline'] : null,
                                        $registration->status !== \App\Enums\RegistrationStatus::Cancelled ? ['cancel', 'Cancel', 'btn-ghost'] : null,
                                    ]) as [$action, $label, $style])
                                        <form method="POST" action="{{ route('admin.events.registrations.update', $registration) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="action" value="{{ $action }}"><button class="btn {{ $style }} btn-sm">{{ $label }}</button></form>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty title="No registrations yet" icon="users" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $registrations->links() }}</div>
        </div>
    @else
        <div class="card card-pad">
            <div class="prose-church">{!! $event->description !!}</div>
            <p class="mt-4 text-sm text-muted">Online registration is off for this event.</p>
        </div>
    @endif
</x-layouts.admin>
