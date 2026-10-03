<x-layouts.admin title="Volunteers">
    <x-page-header title="Volunteers" description="Everyone serving in the ministries you look after." />

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" class="min-w-48 grow" />
        <x-form.select name="ministry" label="Ministry" :options="$ministries" :value="request('ministry')" placeholder="All" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="All" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Volunteer</th><th>Ministry</th><th>Role</th><th>Skills</th><th>Availability</th><th>Joined</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($volunteers as $volunteer)
                    <tr>
                        <td><a href="{{ route('admin.members.show', $volunteer->profile) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$volunteer->profile" size="size-8" /> {{ $volunteer->profile->full_name }}</a></td>
                        <td><a href="{{ route('admin.ministries.show', $volunteer->ministry) }}" class="hover:text-brand">{{ $volunteer->ministry->name }}</a></td>
                        <td class="text-sm">{{ $volunteer->role?->name ?? '—' }}</td>
                        <td class="text-xs">{{ implode(', ', $volunteer->skills ?? []) ?: '—' }}</td>
                        <td class="text-xs">{{ collect($volunteer->availability ?? [])->map(fn ($a) => \App\Enums\Availability::tryFrom($a)?->label())->filter()->implode(', ') ?: '—' }}</td>
                        <td class="text-sm whitespace-nowrap">{{ $volunteer->joined_at?->translatedFormat('j M Y') }}</td>
                        <td><x-badge :value="$volunteer->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No volunteers found" icon="hand" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $volunteers->links() }}</div>
</x-layouts.admin>
