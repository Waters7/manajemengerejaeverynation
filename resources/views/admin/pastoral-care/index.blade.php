<x-layouts.admin title="Pastoral Care">
    <x-page-header title="Pastoral care" description="Restricted. Details and notes are encrypted and visible only to the pastoral team.">
        @can('create', \App\Models\PastoralCareRequest::class)
            <a href="{{ route('admin.pastoral-care.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Open care case</a>
        @endcan
    </x-page-header>
    <x-filter-bar>
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="Open & in progress" />
        <x-form.select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All" />
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="mine" value="1" class="checkbox" @checked(request('mine'))> Assigned to me</label>
    </x-filter-bar>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Subject</th><th>Person</th><th>Category</th><th>Priority</th><th>Assigned</th><th>Status</th><th>Opened</th></tr></thead>
            <tbody>
                @forelse ($cases as $case)
                    <tr>
                        <td><a href="{{ route('admin.pastoral-care.show', $case) }}" class="font-bold hover:text-brand">{{ $case->subject }}</a></td>
                        <td class="text-sm">{{ $case->profile?->full_name ?? $case->requester_name ?? '—' }}</td>
                        <td class="text-sm">{{ $case->category?->name ?? '—' }}</td>
                        <td><x-badge :value="$case->priority" /></td>
                        <td class="text-sm">{{ $case->assignee?->name ?? '—' }}</td>
                        <td><x-badge :value="$case->status" /></td>
                        <td class="text-sm whitespace-nowrap text-muted">{{ $case->created_at->translatedFormat('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No open care cases" icon="heart" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $cases->links() }}</div>
</x-layouts.admin>
