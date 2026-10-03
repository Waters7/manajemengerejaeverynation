<x-layouts.admin :title="$ministry->name">
    <x-page-header :title="$ministry->name" :description="$ministry->description" :back="route('admin.ministries.index')" :eyebrow="'Coordinator: '.($ministry->coordinator?->name ?? '—')">
        @if ($canManage)
            <a href="{{ route('admin.serving.index', ['ministry' => $ministry->id]) }}" class="btn btn-outline btn-sm"><x-icon name="calendar" class="size-4" /> Serving schedule</a>
            <a href="{{ route('admin.ministries.edit', $ministry) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
        @endif
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5">
                <h2 class="font-extrabold uppercase">Volunteers</h2>
                @if ($canManage)
                    <form method="POST" action="{{ route('admin.ministries.members.store', $ministry) }}" class="flex flex-wrap gap-2">
                        @csrf
                        <select name="profile_id" class="input !w-56 !py-1.5" required>
                            <option value="">Add a person…</option>
                            @foreach ($candidates as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        <button class="btn btn-dark btn-sm">Add</button>
                    </form>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Volunteer</th><th>Role</th><th>Skills</th><th>Availability</th><th>Joined</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td><a href="{{ route('admin.members.show', $member->profile) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$member->profile" size="size-8" /> {{ $member->profile->full_name }}</a></td>
                                <td>
                                    @if ($canManage)
                                        <form method="POST" action="{{ route('admin.volunteers.update', $member) }}">@csrf @method('PATCH')
                                            <select name="ministry_role_id" class="input !w-36 !py-1 text-xs" onchange="this.form.submit()">
                                                <option value="">—</option>
                                                @foreach ($ministry->roles as $role)<option value="{{ $role->id }}" @selected($member->ministry_role_id === $role->id)>{{ $role->name }}</option>@endforeach
                                            </select>
                                        </form>
                                    @else
                                        {{ $member->role?->name ?? '—' }}
                                    @endif
                                </td>
                                <td class="text-xs">{{ implode(', ', $member->skills ?? []) ?: '—' }}</td>
                                <td class="text-xs">{{ collect($member->availability ?? [])->map(fn ($a) => $availability[$a] ?? $a)->implode(', ') ?: '—' }}</td>
                                <td class="text-sm whitespace-nowrap">{{ $member->joined_at?->translatedFormat('j M Y') }}</td>
                                <td>
                                    @if ($canManage)
                                        <form method="POST" action="{{ route('admin.volunteers.update', $member) }}">@csrf @method('PATCH')
                                            <select name="status" class="input !w-32 !py-1 text-xs" onchange="this.form.submit()">
                                                @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($member->status->value === $value)>{{ $label }}</option>@endforeach
                                            </select>
                                        </form>
                                    @else
                                        <x-badge :value="$member->status" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty title="No volunteers yet" icon="hand" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="flex items-center justify-between border-b border-line p-5">
                    <h2 class="font-extrabold uppercase">Waiting applications</h2>
                    <a href="{{ route('admin.volunteer-applications.index', ['ministry' => $ministry->id]) }}" class="text-sm font-bold text-brand">All →</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($applications as $application)
                        <li><a href="{{ route('admin.volunteer-applications.show', $application) }}" class="flex items-center justify-between gap-2 px-5 py-3 text-sm hover:bg-slate-50"><span class="font-bold">{{ $application->name }}</span><x-badge :value="$application->status" /></a></li>
                    @empty
                        <li class="p-5 text-sm text-muted">No one waiting.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">Upcoming serving</h2>
                <ul class="divide-y divide-line">
                    @forelse ($schedule as $slot)
                        <li class="flex justify-between gap-2 px-5 py-2.5 text-sm"><span>{{ $slot->profile->displayName() }}{{ $slot->role ? ' · '.$slot->role->name : '' }}</span><span class="text-muted">{{ $slot->serve_date->translatedFormat('D, j M') }}</span></li>
                    @empty
                        <li class="p-5 text-sm text-muted">Nothing scheduled.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Roles</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($ministry->roles as $role)
                        <li class="flex items-center justify-between text-sm">
                            <span class="font-semibold">{{ $role->name }}</span>
                            @if ($canManage)
                                <form method="POST" action="{{ route('admin.ministries.roles.destroy', $role) }}" data-confirm="Remove role {{ $role->name }}?">@csrf @method('DELETE')<button class="text-muted hover:text-danger"><x-icon name="x" class="size-4" /></button></form>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($canManage)
                    <form method="POST" action="{{ route('admin.ministries.roles.store', $ministry) }}" class="mt-4 flex gap-2">
                        @csrf
                        <input name="name" class="input !py-1.5" placeholder="New role" required>
                        <button class="btn btn-outline btn-sm">Add</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
