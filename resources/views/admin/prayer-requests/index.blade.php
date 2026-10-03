<x-layouts.admin title="Prayer Requests">
    <x-page-header title="Prayer requests" description="Carry one another's burdens. Requests marked “Pastor only” are visible to pastors only.">
        <a href="{{ route('prayer.create') }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Public form</a>
    </x-page-header>
    <x-filter-bar>
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="Open requests" />
        <x-form.select name="visibility" label="Visibility" :options="$visibilities" :value="request('visibility')" placeholder="All I can see" />
    </x-filter-bar>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($prayers as $prayer)
            <a href="{{ route('admin.prayer-requests.show', $prayer) }}" class="card card-hover card-pad flex flex-col">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-extrabold">{{ $prayer->requesterName() }}</p>
                    <x-badge :value="$prayer->status" />
                </div>
                <p class="mt-3 line-clamp-4 grow text-sm text-slate-700">{{ $prayer->request }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-muted">
                    <x-badge :value="$prayer->visibility" />
                    @if ($prayer->lifeGroup)<span>{{ $prayer->lifeGroup->name }}</span>@endif
                    <span>· {{ $prayer->created_at->diffForHumans() }}</span>
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty title="No prayer requests here" icon="pray" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $prayers->links() }}</div>
</x-layouts.admin>
