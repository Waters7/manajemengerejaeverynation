<x-layouts.admin :title="$group->name">
    @php $canManage = auth()->user()->can('update', $group); @endphp
    <x-page-header :title="$group->name" :back="route('admin.lifegroups.index')" :eyebrow="$group->category->label().($group->campus ? ' · '.$group->campus->name : '')"
        :description="trim($group->scheduleLabel().' · '.($group->location ?? $group->area), ' ·')">
        @if ($canManage)
            <a href="{{ route('admin.meetings.create', $group) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Record meeting</a>
            <a href="{{ route('admin.lifegroups.edit', $group) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
        @endif
        <a href="{{ route('lifegroups.show', $group->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Public page</a>
    </x-page-header>

    {{-- Overview --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['Members', $summary['members'], 'users'],
            ['Visitors', $summary['visitors'], 'user-plus'],
            ['Being discipled', $summary['being_discipled'], 'sprout'],
            ['Disciplers', $summary['disciplers'], 'tree'],
            ['Potential leaders', $summary['potential_leaders'], 'trend'],
            ['Attendance', $summary['attendance_rate'] !== null ? $summary['attendance_rate'].'%' : '—', 'check-circle'],
        ] as [$label, $value, $icon])
            <div class="card p-4"><x-icon :name="$icon" class="size-5 text-brand" /><p class="mt-2 text-2xl font-extrabold">{{ $value }}</p><p class="text-[0.65rem] font-bold tracking-wider text-muted uppercase">{{ $label }}</p></div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Members & visitors --}}
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5">
                    <h2 class="font-extrabold uppercase">Members & visitors</h2>
                    @if ($canManage)
                        <form method="POST" action="{{ route('admin.lifegroups.members.store', $group) }}" class="flex flex-wrap gap-2">
                            @csrf
                            <select name="profile_id" class="input !w-56 !py-1.5" required>
                                <option value="">Add a person…</option>
                                @foreach ($candidates as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                            <select name="role" class="input !w-32 !py-1.5">@foreach ($roles as $value => $label)<option value="{{ $value }}" @selected($value === 'member')>{{ $label }}</option>@endforeach</select>
                            <button class="btn btn-dark btn-sm">Add</button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Name</th><th>Role</th><th>Journey</th><th>Discipler</th><th>Joined</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($memberships as $membership)
                                <tr @class(['opacity-50' => $membership->status !== 'active'])>
                                    <td><a href="{{ route('admin.members.show', $membership->profile) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$membership->profile" size="size-8" /> {{ $membership->profile->full_name }}</a></td>
                                    <td>
                                        @if ($canManage && $membership->status === 'active')
                                            <form method="POST" action="{{ route('admin.lifegroups.members.update', $membership) }}">@csrf @method('PATCH')
                                                <select name="role" class="input !w-32 !py-1 text-xs" onchange="this.form.submit()">@foreach ($roles as $value => $label)<option value="{{ $value }}" @selected($membership->role->value === $value)>{{ $label }}</option>@endforeach</select>
                                            </form>
                                        @else
                                            <x-badge :value="$membership->role" />
                                        @endif
                                    </td>
                                    <td class="text-sm">{{ $membership->profile->currentStage?->name ?? '—' }}<span class="block text-xs text-muted">{{ $membership->profile->currentProgram?->name }}</span></td>
                                    <td class="text-sm">{{ $membership->profile->activeDiscipler?->discipler?->displayName() ?? '—' }}</td>
                                    <td class="text-sm whitespace-nowrap">{{ $membership->joined_at?->translatedFormat('j M Y') }}</td>
                                    <td class="text-right">
                                        @if ($canManage && $membership->status === 'active' && $membership->role !== \App\Enums\LifeGroupRole::Leader)
                                            <form method="POST" action="{{ route('admin.lifegroups.members.destroy', $membership) }}" data-confirm="Mark {{ $membership->profile->displayName() }} as moved on from this LifeGroup?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm">Moved on</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Meetings & attendance --}}
            <div class="card">
                <div class="flex items-center justify-between border-b border-line p-5">
                    <h2 class="font-extrabold uppercase">Meetings & attendance</h2>
                    <a href="{{ route('admin.meetings.index', ['lifegroup' => $group->id]) }}" class="text-sm font-bold text-brand">History →</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($meetings as $meeting)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <p class="font-bold">{{ $meeting->meeting_date->translatedFormat('l, j M Y') }} · {{ $meeting->topic ?: 'Meeting' }}</p>
                                <p class="text-xs text-muted">{{ $meeting->location }}{{ $meeting->visitor_count ? ' · '.$meeting->visitor_count.' visitors' : '' }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold tabular-nums">{{ $meeting->present_count }}/{{ $meeting->attendances_count }}</span>
                                @if ($canManage)<a href="{{ route('admin.meetings.edit', $meeting) }}" class="btn btn-outline btn-sm">Edit</a>@endif
                            </div>
                        </li>
                    @empty
                        <li class="p-5 text-sm text-muted">No meetings recorded yet.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Discipleship --}}
            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">Discipleship in this group</h2>
                <ul class="divide-y divide-line">
                    @forelse ($discipleship as $progress)
                        <li class="grid items-center gap-3 px-5 py-3 sm:grid-cols-3">
                            <a href="{{ route('admin.members.show', $progress->profile) }}#discipleship" class="font-bold hover:text-brand">{{ $progress->profile->full_name }}</a>
                            <span class="text-sm">{{ $progress->program->name }} <span class="text-xs text-muted">{{ $progress->discipler ? '· with '.$progress->discipler->displayName() : '' }}</span></span>
                            @if ($progress->program->chapters->count())<x-progress :value="$progress->completedUnits()" :total="$progress->program->chapters->count()" />@else<x-badge :value="$progress->status" />@endif
                        </li>
                    @empty
                        <li class="p-5 text-sm text-muted">No one in this group is currently in a program.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Leaders</h2>
                @foreach (array_filter([$group->leader, $group->coLeader]) as $leader)
                    <a href="{{ route('admin.members.show', $leader) }}" class="mt-3 flex items-center gap-3 font-bold hover:text-brand"><x-avatar :profile="$leader" /> {{ $leader->full_name }}</a>
                @endforeach
                @if ($group->whatsapp_invite_url)
                    <p class="mt-4 flex items-center gap-2 text-xs text-muted"><x-icon name="lock" class="size-3.5" /> WhatsApp invite link is set (shared only after approval).</p>
                @endif
            </div>

            <div class="card">
                <div class="flex items-center justify-between border-b border-line p-5">
                    <h2 class="font-extrabold uppercase">Join requests</h2>
                    <a href="{{ route('admin.join-requests.index', ['lifegroup' => $group->id]) }}" class="text-sm font-bold text-brand">All →</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($requests as $joinRequest)
                        <li class="flex items-center justify-between gap-2 px-5 py-3 text-sm">
                            <span><strong>{{ $joinRequest->name }}</strong><span class="block text-xs text-muted">{{ $joinRequest->created_at->diffForHumans() }}</span></span>
                            <x-badge :value="$joinRequest->status" />
                        </li>
                    @empty
                        <li class="p-5 text-sm text-muted">No open requests.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">Leadership pipeline</h2>
                <ul class="divide-y divide-line">
                    @forelse ($pipeline as $candidate)
                        <li class="flex items-center justify-between gap-2 px-5 py-3 text-sm"><span class="font-bold">{{ $candidate->profile->full_name }}</span><x-badge :value="$candidate->stage" /></li>
                    @empty
                        <li class="p-5 text-sm text-muted">No one in the pipeline yet.</li>
                    @endforelse
                </ul>
            </div>

            @if ($prayers->isNotEmpty())
                <div class="card">
                    <h2 class="border-b border-line p-5 font-extrabold uppercase">Prayer requests</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($prayers as $prayer)
                            <li class="px-5 py-3 text-sm"><a href="{{ route('admin.prayer-requests.show', $prayer) }}" class="hover:text-brand"><strong>{{ $prayer->requesterName() }}</strong> · {{ Str::limit($prayer->request, 70) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($events->isNotEmpty())
                <div class="card">
                    <h2 class="border-b border-line p-5 font-extrabold uppercase">Events</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($events as $event)
                            <li class="px-5 py-3 text-sm"><strong>{{ $event->title }}</strong> · {{ $event->starts_at->translatedFormat('j M Y') }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
