<x-layouts.admin title="Campus Ministry">
    <x-page-header title="Campus Ministry" description="CHANGE THE CAMPUS. CHANGE THE WORLD.">
        @can('create', \App\Models\Campus::class)
            <a href="{{ route('admin.campuses.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Add campus</a>
        @endcan
    </x-page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($campuses as $campus)
            <a href="{{ route('admin.campuses.show', $campus) }}" class="card card-hover card-pad">
                <x-icon name="campus" class="size-7 text-brand" />
                <h2 class="mt-3 text-lg font-extrabold">{{ $campus->name }} @unless ($campus->is_active)<x-badge color="gray">Inactive</x-badge>@endunless</h2>
                <p class="text-sm text-muted">{{ $campus->ministers->pluck('name')->implode(', ') ?: 'No campus leader assigned' }}</p>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-extrabold">{{ $campus->students_count }}</p><p class="text-[0.6rem] font-bold text-muted uppercase">Students</p></div>
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-extrabold">{{ $campus->life_groups_count }}</p><p class="text-[0.6rem] font-bold text-muted uppercase">LifeGroups</p></div>
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-extrabold">{{ $campus->events_count }}</p><p class="text-[0.6rem] font-bold text-muted uppercase">Events</p></div>
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty title="No campuses assigned" icon="campus">Ask an admin to assign your campus scope.</x-empty></div>
        @endforelse
    </div>
</x-layouts.admin>
