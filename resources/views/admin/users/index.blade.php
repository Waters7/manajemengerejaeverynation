<x-layouts.admin title="Users">
    <x-page-header title="User accounts" description="Public sign-ups start as USER (pending verification). Ministry roles are granted manually here." />

    <x-filter-bar>
        <x-form.input name="q" type="search" label="Search" :value="request('q')" placeholder="Name or email" class="min-w-56 grow" />
        <x-form.select name="role" label="Role" :options="$roles" :value="request('role')" placeholder="All" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="All" />
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Last login</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $account)
                    <tr>
                        <td><span class="flex items-center gap-3"><x-avatar :profile="$account->profile" :name="$account->name" size="size-8" /><span class="font-bold">{{ $account->name }}</span></span></td>
                        <td class="text-sm">{{ $account->email }}</td>
                        <td class="text-xs">{{ $account->roles->map(fn ($r) => \App\Enums\Role::tryFrom($r->name)?->label() ?? $r->name)->implode(', ') }}</td>
                        <td><x-badge :value="$account->account_status" /></td>
                        <td class="text-sm text-muted">{{ $account->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="text-right"><a href="{{ route('admin.users.edit', $account) }}" class="btn btn-outline btn-sm">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty title="No accounts found" icon="user" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin>
