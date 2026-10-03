<x-layouts.member title="Serving">
    <x-page-header title="Serving" description="Terima kasih sudah melayani bersama Every Nation Bekasi.">
        <a href="{{ route('get-involved.serve') }}" class="btn btn-primary btn-sm">Apply to serve</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($memberships as $membership)
                <div class="card card-pad flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-extrabold">{{ $membership->ministry->name }}</p>
                        <p class="text-sm text-muted">{{ $membership->role?->name ?? 'Team member' }} · since {{ $membership->joined_at?->translatedFormat('M Y') }}</p>
                        @if ($membership->ministry->coordinator)<p class="mt-1 text-sm text-muted">Coordinator: {{ $membership->ministry->coordinator->displayName() }}</p>@endif
                    </div>
                    <x-badge :value="$membership->status" />
                </div>
            @empty
                <div class="card"><x-empty title="Not serving yet" icon="hand">Setiap talenta berharga — ayo melayani bersama!</x-empty></div>
            @endforelse

            @if ($applications->isNotEmpty())
                <h2 class="pt-6 font-extrabold uppercase">My applications</h2>
                @foreach ($applications as $application)
                    <div class="card flex items-center justify-between gap-3 p-4">
                        <span class="text-sm"><strong>{{ $application->ministries->pluck('name')->implode(', ') }}</strong> · {{ $application->created_at->translatedFormat('j M Y') }}</span>
                        <x-badge :value="$application->status" />
                    </div>
                @endforeach
            @endif
        </div>

        <div class="card card-pad h-fit">
            <h2 class="font-extrabold uppercase">Upcoming schedule</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($schedule as $slot)
                    <li class="rounded-xl bg-slate-50 p-3 text-sm">
                        <p class="font-bold">{{ $slot->serve_date->translatedFormat('l, j M') }}</p>
                        <p class="text-muted">{{ $slot->ministry->name }}{{ $slot->role ? ' · '.$slot->role->name : '' }}{{ $slot->service_label ? ' · '.$slot->service_label : '' }}</p>
                    </li>
                @empty
                    <li class="text-sm text-muted">Belum ada jadwal pelayanan.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.member>
