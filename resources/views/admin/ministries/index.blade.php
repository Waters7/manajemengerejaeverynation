<x-layouts.admin title="Ministries">
    <x-page-header title="Ministries" description="Teams serving God and people. Volunteers grow here — serving never grants a system role.">
        @can('create', \App\Models\Ministry::class)
            <a href="{{ route('admin.ministries.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New ministry</a>
        @endcan
    </x-page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($ministries as $ministry)
            <a href="{{ route('admin.ministries.show', $ministry) }}" class="card card-hover card-pad">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-extrabold">{{ $ministry->name }}</h2>
                    @if (! $ministry->is_active)<x-badge color="gray">Inactive</x-badge>@elseif ($ministry->accepting_volunteers)<x-badge color="green">Needs volunteers</x-badge>@endif
                </div>
                <p class="mt-1 text-sm text-muted">Coordinator: {{ $ministry->coordinator?->name ?? '—' }}</p>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-extrabold">{{ $ministry->active_members_count }}</p><p class="text-[0.6rem] font-bold text-muted uppercase">Active</p></div>
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-extrabold">{{ $ministry->orientation_count }}</p><p class="text-[0.6rem] font-bold text-muted uppercase">Orientation</p></div>
                    <div @class(['rounded-xl p-2', 'bg-amber-50' => $ministry->waiting_count, 'bg-slate-50' => ! $ministry->waiting_count])><p class="text-lg font-extrabold">{{ $ministry->waiting_count }}</p><p class="text-[0.6rem] font-bold text-muted uppercase">Waiting</p></div>
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty title="No ministries assigned to you" icon="sparkles" /></div>
        @endforelse
    </div>
</x-layouts.admin>
