<x-layouts.admin title="Audit Logs">
    <x-page-header title="Audit logs" description="Create, edit, delete, publish, approve, reject, assign, role change, login and export events." />
    <x-filter-bar>
        <x-form.select name="action" label="Action" :options="$actions" :value="request('action')" placeholder="All" />
        <x-form.select name="user" label="User" :options="$users" :value="request('user')" placeholder="Anyone" />
        <x-form.select name="model" label="Record type" :options="$models" :value="request('model')" placeholder="All" />
        <x-form.input name="from" type="date" label="From" :value="request('from')" />
        <x-form.input name="to" type="date" label="To" :value="request('to')" />
    </x-filter-bar>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Description</th><th>Changes</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="text-xs whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                        <td><x-badge :value="$log->action" /></td>
                        <td class="text-xs">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                        <td class="max-w-72 text-sm">{{ $log->description }}</td>
                        <td class="max-w-80 text-xs">
                            @if ($log->new_values)
                                <details>
                                    <summary class="cursor-pointer font-semibold text-brand">{{ count($log->new_values) }} field(s)</summary>
                                    <dl class="mt-2 space-y-1">
                                        @foreach ($log->new_values as $field => $value)
                                            <div><dt class="inline font-bold">{{ $field }}:</dt> <dd class="inline break-all">@if (isset($log->old_values[$field]))<span class="text-muted line-through">{{ Str::limit(is_array($log->old_values[$field]) ? json_encode($log->old_values[$field]) : (string) $log->old_values[$field], 60) }}</span> → @endif{{ Str::limit(is_array($value) ? json_encode($value) : (string) $value, 80) }}</dd></div>
                                        @endforeach
                                    </dl>
                                </details>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-xs text-muted">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No log entries" icon="shield" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
