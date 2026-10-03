<x-layouts.admin title="Volunteer Applications">
    <x-page-header title="Volunteer applications" description="People who would love to serve. Accepting a volunteer adds them to the ministry — it never grants system access." />

    <x-filter-bar>
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="Awaiting response" />
        <x-form.select name="ministry" label="Ministry" :options="$ministries" :value="request('ministry')" placeholder="All" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Ministry</th><th>Availability</th><th>Applied</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td><a href="{{ route('admin.volunteer-applications.show', $application) }}" class="font-bold hover:text-brand">{{ $application->name }}</a><span class="block text-xs text-muted">{{ \App\Services\WhatsApp::display($application->whatsapp) }}</span></td>
                        <td class="text-sm">{{ $application->ministries->pluck('name')->implode(', ') }}</td>
                        <td class="text-xs">{{ collect($application->availability ?? [])->map(fn ($a) => \App\Enums\Availability::tryFrom($a)?->label())->filter()->implode(', ') ?: '—' }}</td>
                        <td class="text-sm whitespace-nowrap">{{ $application->created_at->translatedFormat('j M Y') }}</td>
                        <td><x-badge :value="$application->status" /></td>
                        <td class="text-right"><a href="{{ route('admin.volunteer-applications.show', $application) }}" class="btn btn-outline btn-sm">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No applications waiting" icon="document" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $applications->links() }}</div>
</x-layouts.admin>
