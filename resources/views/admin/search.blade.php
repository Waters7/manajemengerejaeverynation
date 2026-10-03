<x-layouts.admin title="Search">
    <x-page-header :title="$term ? 'Results for “'.$term.'”' : 'Search'" />
    <form method="GET" class="mb-6 flex max-w-xl gap-2">
        <input type="search" name="q" value="{{ $term }}" class="input" placeholder="Name, WhatsApp, email, LifeGroup, event…" autofocus>
        <button class="btn btn-primary">Search</button>
    </form>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <h2 class="border-b border-line p-4 font-extrabold uppercase">People ({{ $people->count() }})</h2>
            <ul class="divide-y divide-line">
                @forelse ($people as $person)
                    <li><a href="{{ route('admin.members.show', $person) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50">
                        <x-avatar :profile="$person" size="size-9" />
                        <span class="grow"><span class="block font-bold">{{ $person->full_name }}</span><span class="text-xs text-muted">{{ \App\Services\WhatsApp::display($person->whatsapp) }} · {{ $person->activeLifeGroups->first()?->name ?? 'No LifeGroup' }}</span></span>
                        <x-badge :value="$person->member_status" />
                    </a></li>
                @empty
                    <li class="p-4 text-sm text-muted">No people found.</li>
                @endforelse
            </ul>
        </div>
        <div class="space-y-6">
            <div class="card">
                <h2 class="border-b border-line p-4 font-extrabold uppercase">LifeGroups</h2>
                <ul class="divide-y divide-line">
                    @forelse ($groups as $group)
                        <li><a href="{{ route('admin.lifegroups.show', $group) }}" class="block px-4 py-3 font-bold hover:bg-slate-50">{{ $group->name }}</a></li>
                    @empty
                        <li class="p-4 text-sm text-muted">—</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <h2 class="border-b border-line p-4 font-extrabold uppercase">Events</h2>
                <ul class="divide-y divide-line">
                    @forelse ($events as $event)
                        <li><a href="{{ route('admin.events.show', $event) }}" class="block px-4 py-3 hover:bg-slate-50"><span class="font-bold">{{ $event->title }}</span> <span class="text-xs text-muted">{{ $event->starts_at->translatedFormat('j M Y') }}</span></a></li>
                    @empty
                        <li class="p-4 text-sm text-muted">—</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>
