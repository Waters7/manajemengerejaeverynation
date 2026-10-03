<x-layouts.admin :title="$campus->name">
    <x-page-header :title="$campus->name" :description="$campus->address" eyebrow="Campus Ministry" :back="route('admin.campuses.index')">
        @can('update', $campus)<a href="{{ route('admin.campuses.edit', $campus) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>@endcan
        @can('events.manage')<a href="{{ route('admin.events.create', ['campus' => $campus->id]) }}" class="btn btn-outline btn-sm"><x-icon name="calendar" class="size-4" /> Campus event</a>@endcan
        @can('create', \App\Models\LifeGroup::class)<a href="{{ route('admin.lifegroups.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Campus LifeGroup</a>@endcan
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stages as $row)
            <div class="card p-4"><span class="block h-1.5 w-8 rounded-full" style="background: {{ $row['stage']->color }}"></span><p class="mt-2 text-2xl font-extrabold">{{ $row['count'] }}</p><p class="text-[0.65rem] font-bold tracking-wider text-muted uppercase">{{ $row['stage']->name }}</p></div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <h2 class="border-b border-line p-5 font-extrabold uppercase">Students ({{ $students->total() }})</h2>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Name</th><th>LifeGroup</th><th>Stage</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr>
                                <td><a href="{{ route('admin.members.show', $student) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$student" size="size-8" /> {{ $student->full_name }}</a></td>
                                <td class="text-sm">{{ $student->activeLifeGroups->first()?->name ?? '—' }}</td>
                                <td class="text-sm">{{ $student->currentStage?->name ?? '—' }}</td>
                                <td><x-badge :value="$student->member_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty title="No students linked to this campus yet" icon="users" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $students->links() }}</div>
        </div>

        <div class="space-y-6">
            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Campus leaders</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($leaders as $leader)<li class="font-semibold">{{ $leader->name }}</li>@empty<li class="text-muted">None assigned yet.</li>@endforelse
                </ul>
            </div>
            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">Campus LifeGroups</h2>
                <ul class="divide-y divide-line">
                    @forelse ($lifeGroups as $group)
                        <li><a href="{{ route('admin.lifegroups.show', $group) }}" class="flex justify-between gap-2 px-5 py-3 text-sm hover:bg-slate-50"><span class="font-bold">{{ $group->name }}</span><span class="text-muted">{{ $group->members_count }} people</span></a></li>
                    @empty
                        <li class="p-5 text-sm text-muted">No campus LifeGroups.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">One 2 One</h2>
                <ul class="divide-y divide-line">
                    @forelse ($one2one as $progress)
                        <li class="px-5 py-3 text-sm"><a href="{{ route('admin.progress.show', $progress) }}" class="font-bold hover:text-brand">{{ $progress->profile->full_name }}</a><span class="block text-xs text-muted">with {{ $progress->discipler?->displayName() ?? '—' }}</span></li>
                    @empty
                        <li class="p-5 text-sm text-muted">No active One 2 One.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">Campus events</h2>
                <ul class="divide-y divide-line">
                    @forelse ($events as $event)
                        <li class="px-5 py-3 text-sm"><span class="font-bold">{{ $event->title }}</span> <span class="text-muted">· {{ $event->starts_at->translatedFormat('j M Y') }}</span></li>
                    @empty
                        <li class="p-5 text-sm text-muted">No campus events.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <h2 class="border-b border-line p-5 font-extrabold uppercase">Student volunteers</h2>
                <ul class="divide-y divide-line">
                    @forelse ($volunteers as $volunteer)
                        <li class="px-5 py-3 text-sm"><span class="font-bold">{{ $volunteer->profile->full_name }}</span> <span class="text-muted">· {{ $volunteer->ministry->name }}</span></li>
                    @empty
                        <li class="p-5 text-sm text-muted">No student volunteers yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>
