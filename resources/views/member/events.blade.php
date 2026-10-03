<x-layouts.member title="My Events">
    <x-page-header title="My events">
        <a href="{{ route('events.index') }}" class="btn btn-primary btn-sm">Browse events</a>
    </x-page-header>

    <div class="card divide-y divide-line">
        @forelse ($upcoming as $registration)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-extrabold">{{ $registration->event->title }}</p>
                    <p class="text-sm text-muted">{{ $registration->event->starts_at->translatedFormat('l, j F Y · H:i') }} · {{ $registration->event->location }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-badge :value="$registration->status" />
                    @if ($registration->status !== \App\Enums\RegistrationStatus::Cancelled)
                        <a href="{{ route('events.ticket', [$registration->event->slug, $registration->code]) }}" class="btn btn-outline btn-sm"><x-icon name="qr" class="size-4" /> Ticket</a>
                        <form method="POST" action="{{ route('member.events.cancel', $registration) }}" data-confirm="Batalkan pendaftaran ini?">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm">Cancel</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <x-empty title="No upcoming events" icon="calendar" />
        @endforelse
    </div>

    @if ($past->isNotEmpty())
        <h2 class="mt-10 mb-3 font-extrabold uppercase">Past events</h2>
        <div class="card divide-y divide-line">
            @foreach ($past as $registration)
                <div class="flex items-center justify-between gap-3 p-4 text-sm">
                    <span><strong>{{ $registration->event?->title }}</strong> · {{ $registration->event?->starts_at->translatedFormat('j M Y') }}</span>
                    @if ($registration->checked_in_at)<x-badge color="green">Attended</x-badge>@endif
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.member>
