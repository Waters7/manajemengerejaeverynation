<x-layouts.admin title="LifeGroups">
    <x-page-header title="LifeGroups" description="Small communities where people grow in the Word, prayer and friendship.">
        @can('create', \App\Models\LifeGroup::class)
            <a href="{{ route('admin.lifegroups.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New LifeGroup</a>
        @endcan
    </x-page-header>

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-48 grow" />
        <x-form.select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All" />
        @if ($campuses->isNotEmpty())<x-form.select name="campus" label="Campus" :options="$campuses" :value="request('campus')" placeholder="All" />@endif
        <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'multiplied' => 'Multiplied']" :value="request('status', 'active')" />
    </x-filter-bar>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($groups as $group)
            <a href="{{ route('admin.lifegroups.show', $group) }}" class="card card-hover card-pad">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="eyebrow">{{ $group->category->label() }}{{ $group->campus ? ' · '.$group->campus->short_name : '' }}</p>
                        <h2 class="mt-1 text-lg font-extrabold">{{ $group->name }}</h2>
                    </div>
                    @if ($group->pending_join_requests_count)
                        <span class="badge badge-amber">{{ $group->pending_join_requests_count }} request{{ $group->pending_join_requests_count > 1 ? 's' : '' }}</span>
                    @endif
                </div>
                <div class="mt-4 space-y-1 text-sm text-muted">
                    <p class="flex items-center gap-2"><x-icon name="user" class="size-4" /> {{ $group->leader?->full_name ?? 'No leader yet' }}</p>
                    <p class="flex items-center gap-2"><x-icon name="clock" class="size-4" /> {{ $group->scheduleLabel() ?: 'Schedule TBA' }}</p>
                    <p class="flex items-center gap-2"><x-icon name="users" class="size-4" /> {{ $group->members_count }} people</p>
                </div>
                <div class="mt-4 flex gap-2">
                    @if ($group->accepting_members)<x-badge color="green">Open</x-badge>@else<x-badge color="gray">Closed</x-badge>@endif
                    @unless ($group->is_public)<x-badge color="gray">Hidden</x-badge>@endunless
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty title="No LifeGroups found" icon="users" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $groups->links() }}</div>
</x-layouts.admin>
