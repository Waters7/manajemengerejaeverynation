<x-layouts.admin title="Roles & Permissions">
    <x-page-header title="Roles & permissions" description="What each role can do. Data is additionally scoped: leaders see their own LifeGroup, campus ministry their campuses, coordinators their ministries." />

    <div class="space-y-6">
        @foreach ($roles as $role)
            @php $enum = \App\Enums\Role::tryFrom($role->name); $locked = $role->name === \App\Enums\Role::SuperAdmin->value; @endphp
            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="card" x-data="{ open: {{ $loop->first ? 'false' : 'false' }} }">
                @csrf @method('PUT')
                <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between gap-3 p-5 text-left">
                    <span>
                        <span class="text-lg font-extrabold">{{ $enum?->label() ?? $role->name }}</span>
                        <span class="ml-2 text-sm text-muted">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }} · {{ $locked ? 'all' : $role->permissions->count() }} permissions</span>
                    </span>
                    <x-icon name="chevron-down" class="size-5 text-muted transition" x-bind:class="open && 'rotate-180'" />
                </button>
                <div x-show="open" x-cloak class="border-t border-line p-5">
                    @if ($locked)
                        <p class="text-sm text-muted">Super Admin always has full access and cannot be edited.</p>
                    @else
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($permissions as $group => $items)
                                <fieldset>
                                    <legend class="mb-2 text-xs font-bold tracking-wider text-muted uppercase">{{ $group }}</legend>
                                    @foreach ($items as $permission)
                                        <label class="flex items-start gap-2 py-1 text-sm">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="checkbox mt-0.5" @checked($role->permissions->contains('name', $permission->name))>
                                            <span><span class="font-semibold">{{ $permission->name }}</span><span class="block text-xs text-muted">{{ $descriptions[$permission->name] ?? '' }}</span></span>
                                        </label>
                                    @endforeach
                                </fieldset>
                            @endforeach
                        </div>
                        <button class="btn btn-primary btn-sm mt-5">Save permissions</button>
                    @endif
                </div>
            </form>
        @endforeach
    </div>
</x-layouts.admin>
