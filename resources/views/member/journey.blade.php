<x-layouts.member title="My Journey">
    <x-page-header title="My discipleship journey" description="Engage → Establish → Equip → Empower. Setiap langkah adalah bagian dari mengikut Yesus bersama orang lain." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad">
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Current stage</dt><dd class="mt-1 text-lg font-extrabold">{{ $profile->currentStage?->name ?? 'Not started' }}</dd></div>
                    <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Current program</dt><dd class="mt-1 text-lg font-extrabold">{{ $profile->currentProgram?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Discipler</dt><dd class="mt-1 text-lg font-extrabold">{{ $discipler?->discipler?->displayName() ?? '—' }}</dd></div>
                </dl>
            </div>
            @include('partials.baptism', ['profile' => $profile])
            @include('partials.journey', ['stages' => $stages])
        </div>
        <div class="card card-pad h-fit">
            <h2 class="mb-5 font-extrabold uppercase">Timeline</h2>
            @include('partials.timeline', ['timeline' => $timeline])
        </div>
    </div>
</x-layouts.member>
