<x-layouts.admin title="Get Involved Requests">
    <x-page-header title="Get Involved requests" description="People who just connected with us — let's help them take a next step.">
        @can('reports.export')
            <a href="{{ route('admin.involvement.export', request()->query()) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Excel</a>
            <a href="{{ route('admin.involvement.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline btn-sm">CSV</a>
        @endcan
        <a href="{{ route('get-involved') }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Public form</a>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.involvement.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => ! request()->hasAny(['status', 'mine', 'archived'])])>All open</a>
        <a href="{{ route('admin.involvement.index', ['status' => 'not_contacted']) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('status') === 'not_contacted'])>Not contacted</a>
        @foreach ($statuses as $value => $label)
            <a href="{{ route('admin.involvement.index', ['status' => $value]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('status') === $value])>{{ $label }} <span class="text-xs opacity-70">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
        <a href="{{ route('admin.involvement.index', ['mine' => 1]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('mine')])>Assigned to me</a>
        <a href="{{ route('admin.involvement.index', ['archived' => 1]) }}" @class(['chip', '!border-ink !bg-ink !text-white' => request('archived')])>Archived</a>
    </div>

    <x-filter-bar>
        @foreach (request()->only(['status', 'mine', 'archived']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <x-form.input name="q" type="search" label="Search" :value="request('q')" placeholder="Name or WhatsApp" class="min-w-48 grow" />
        <x-form.select name="interest" label="Interest" :options="$interests" :value="request('interest')" placeholder="Any" />
        @if ($campuses->isNotEmpty())<x-form.select name="campus" label="Campus" :options="$campuses" :value="request('campus')" placeholder="Any" />@endif
        <x-form.input name="from" type="date" label="From" :value="request('from')" />
        <x-form.input name="to" type="date" label="To" :value="request('to')" />
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="lifegroup_interest" value="1" class="checkbox" @checked(request('lifegroup_interest'))> LifeGroup interest</label>
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="ministry_interest" value="1" class="checkbox" @checked(request('ministry_interest'))> Ministry interest</label>
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>WhatsApp</th><th>Age</th><th>Area</th><th>Interest</th><th>LifeGroup</th><th>Ministry</th><th>Campus</th><th>Registered</th><th>Assigned to</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($requests as $item)
                    @php $wantsLifeGroup = $item->interests->contains(fn ($i) => $i->action === \App\Enums\InterestAction::Lifegroup); @endphp
                    <tr>
                        <td><a href="{{ route('admin.involvement.show', $item) }}" class="font-bold text-ink hover:text-brand">{{ $item->full_name }}</a><span class="block text-xs text-muted">{{ $item->type->label() }}</span></td>
                        <td class="whitespace-nowrap">{{ \App\Services\WhatsApp::display($item->whatsapp) }}</td>
                        <td>{{ $item->age() ?? '—' }}</td>
                        <td>{{ $item->area ?? '—' }}</td>
                        <td class="max-w-52 text-xs">{{ $item->interests->reject(fn ($i) => in_array($i->action, [\App\Enums\InterestAction::Lifegroup, \App\Enums\InterestAction::Volunteer, \App\Enums\InterestAction::Ministry], true))->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td>@if ($wantsLifeGroup)<x-badge color="blue">Yes</x-badge>@else — @endif</td>
                        <td class="max-w-40 text-xs">{{ $item->ministries->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td class="text-xs">{{ $item->campus?->short_name ?? $item->campus_name ?? '—' }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $item->created_at->translatedFormat('j M Y') }}</td>
                        <td class="text-xs">{{ $item->assignee?->name ?? '—' }}@if ($item->due_date)<span @class(['block', 'font-bold text-amber-600' => $item->due_date->isPast(), 'text-muted' => ! $item->due_date->isPast()])>due {{ $item->due_date->translatedFormat('j M') }}</span>@endif</td>
                        <td><x-badge :value="$item->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="11"><x-empty title="No requests here" icon="hand" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
