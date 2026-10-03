<x-layouts.admin title="Newcomers">
    <x-page-header title="Newcomers" description="Walking with people from their first visit into community and discipleship.">
        <a href="{{ route('connect') }}" target="_blank" class="btn btn-outline btn-sm"><x-icon name="qr" class="size-4" /> Connect Card page</a>
    </x-page-header>

    <div class="mb-6 flex gap-2 overflow-x-auto pb-1">
        @foreach ($journeys as $value => $label)
            <a href="{{ route('admin.newcomers.index', ['journey' => $value]) }}" @class(['card shrink-0 px-4 py-3 text-center min-w-32 card-hover', 'ring-2 ring-brand' => request('journey') === $value])>
                <p class="text-2xl font-extrabold">{{ $counts[$value] ?? 0 }}</p>
                <p class="text-[0.65rem] font-bold tracking-wider text-muted uppercase">{{ $label }}</p>
            </a>
            @unless ($loop->last)<span class="self-center text-slate-300"><x-icon name="chevron-right" class="size-4" /></span>@endunless
        @endforeach
    </div>

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-56 grow" />
        <x-form.select name="journey" label="Journey" :options="$journeys" :value="request('journey')" placeholder="All" />
        <x-form.select name="assigned" label="Follow-up person" :options="collect(['none' => '— Not assigned —'])->union($team)" :value="request('assigned')" placeholder="Anyone" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>First visit</th><th>Interest</th><th>Follow-up</th><th>LifeGroup</th><th>Discipler</th><th>Journey</th></tr></thead>
            <tbody>
                @forelse ($newcomers as $newcomer)
                    @php $profile = $newcomer->profile; @endphp
                    <tr>
                        <td><a href="{{ route('admin.members.show', $profile) }}" class="flex items-center gap-3"><x-avatar :profile="$profile" size="size-9" /><span><span class="block font-bold hover:text-brand">{{ $profile->full_name }}</span><span class="text-xs text-muted">{{ \App\Services\WhatsApp::display($profile->whatsapp) }}</span></span></a></td>
                        <td class="whitespace-nowrap">{{ $newcomer->first_visit_date?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="max-w-56 text-sm">{{ $profile->involvementRequests->flatMap->interests->pluck('name')->unique()->take(3)->implode(', ') ?: '—' }}</td>
                        <td>
                            @can('newcomers.manage')
                                <form method="POST" action="{{ route('admin.newcomers.update', $newcomer) }}">
                                    @csrf @method('PATCH')
                                    <select name="assigned_to" class="input !py-1.5 text-xs" onchange="this.form.submit()">
                                        <option value="">— assign —</option>
                                        @foreach ($team as $id => $name)
                                            <option value="{{ $id }}" @selected($newcomer->assigned_to == $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                {{ $newcomer->assignee?->name ?? '—' }}
                            @endcan
                        </td>
                        <td class="text-sm">{{ $profile->activeLifeGroups->first()?->name ?? '—' }}</td>
                        <td class="text-sm">{{ $profile->activeDiscipler?->discipler?->displayName() ?? '—' }}</td>
                        <td>
                            @can('newcomers.manage')
                                <form method="POST" action="{{ route('admin.newcomers.update', $newcomer) }}">
                                    @csrf @method('PATCH')
                                    <select name="journey_status" class="input !py-1.5 text-xs" onchange="this.form.submit()">
                                        @foreach ($journeys as $value => $label)
                                            <option value="{{ $value }}" @selected($newcomer->journey_status->value === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <x-badge :value="$newcomer->journey_status" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No newcomers here" icon="user-plus" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $newcomers->links() }}</div>
</x-layouts.admin>
