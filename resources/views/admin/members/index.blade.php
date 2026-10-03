<x-layouts.admin title="Members">
    <x-page-header title="People" description="Everyone in our church family — visitors, newcomers, members and leaders.">
        @can('create', \App\Models\Profile::class)
            <a href="{{ route('admin.members.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Add person</a>
        @endcan
    </x-page-header>

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" placeholder="Name, WhatsApp, email" class="min-w-56 grow" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="All" />
        <x-form.select name="stage" label="Stage" :options="$stages" :value="request('stage')" placeholder="All" />
        <x-form.select name="baptism" label="Baptism" :options="$baptismStatuses" :value="request('baptism')" placeholder="All" />
        <x-form.select name="lifegroup" label="LifeGroup" :options="$lifeGroups" :value="request('lifegroup')" placeholder="All" />
        @if ($campuses->isNotEmpty())
            <x-form.select name="campus" label="Campus" :options="$campuses" :value="request('campus')" placeholder="All" />
        @endif
        <x-form.select name="sort" label="Sort" :options="['name' => 'Name A–Z', 'recent' => 'Recently added']" :value="request('sort', 'name')" />
        @if (request('needs'))<input type="hidden" name="needs" value="{{ request('needs') }}">@endif
    </x-filter-bar>

    @if (request('needs') === 'discipler')
        <p class="mb-4 rounded-xl bg-brand-50 px-4 py-2.5 text-sm text-brand-800">Showing connected people who are <strong>ready for a discipler</strong>.</p>
    @endif

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>WhatsApp</th><th>LifeGroup</th><th>Journey</th><th>Status</th><th>Joined</th></tr></thead>
            <tbody>
                @forelse ($people as $person)
                    <tr>
                        <td>
                            <a href="{{ route('admin.members.show', $person) }}" class="flex items-center gap-3">
                                <x-avatar :profile="$person" size="size-9" />
                                <span>
                                    <span class="block font-bold text-ink hover:text-brand">{{ $person->full_name }}</span>
                                    <span class="block text-xs text-muted">{{ $person->area ?? $person->campus?->short_name ?? $person->email }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="whitespace-nowrap">{{ \App\Services\WhatsApp::display($person->whatsapp) ?: '—' }}</td>
                        <td>{{ $person->activeLifeGroups->first()?->name ?? '—' }}</td>
                        <td>
                            {{ $person->currentStage?->name ?? '—' }}
                            @if ($person->isBaptized())<span class="block text-xs text-success">Baptized</span>@endif
                        </td>
                        <td><x-badge :value="$person->member_status" /></td>
                        <td class="whitespace-nowrap text-muted">{{ $person->join_date?->translatedFormat('M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No people match these filters" icon="users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $people->links() }}</div>
</x-layouts.admin>
