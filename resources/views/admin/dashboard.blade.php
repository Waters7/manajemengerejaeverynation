<x-layouts.admin title="Dashboard">
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">{{ now()->translatedFormat('l, j F Y') }}</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Hi {{ $user->displayName() }} 👋</h1>
            <p class="mt-1 text-muted">Here's what's happening in the community{{ $churchWide ? '' : ' you care for' }}.</p>
        </div>
        @can('involvement.view')
            <a href="{{ route('admin.involvement.index', ['status' => 'new']) }}" class="btn btn-primary btn-sm"><x-icon name="user-plus" class="size-4" /> New connections</a>
        @endcan
    </div>

    @foreach ($announcements as $announcement)
        <div class="card mb-4 flex gap-3 border-brand/30 bg-brand-50/50 p-4">
            <x-icon name="megaphone" class="size-5 shrink-0 text-brand" />
            <div><p class="font-bold">{{ $announcement->title }}</p><p class="text-sm whitespace-pre-line text-slate-600">{{ $announcement->body }}</p></div>
        </div>
    @endforeach

    {{-- Headline metrics — every tile is clickable --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active members" :value="number_format($metrics['active_members'])" icon="users" :href="route('admin.members.index', ['status' => 'member'])" />
        <x-stat label="Newcomers" :value="number_format($metrics['newcomers'])" icon="user-plus" tone="green" :href="route('admin.newcomers.index')" />
        <x-stat label="Active LifeGroups" :value="number_format($metrics['active_lifegroups'])" icon="heart" tone="violet" :href="route('admin.lifegroups.index')" />
        <x-stat label="People being discipled" :value="number_format($metrics['being_discipled'])" icon="sprout" tone="green" :href="route('admin.journey.index', ['status' => 'in_progress'])" />
        <x-stat label="Active disciplers" :value="number_format($metrics['disciplers'])" icon="tree" :href="route('admin.disciplers.index')" />
        <x-stat label="Potential leaders" :value="number_format($metrics['potential_leaders'])" icon="trend" tone="amber" :href="route('admin.leadership.index')" />
        <x-stat label="Upcoming classes" :value="number_format($metrics['upcoming_classes'])" icon="academic" tone="violet" :href="route('admin.classes.index')" />
        <x-stat label="Needs follow-up" :value="number_format($metrics['needs_follow_up'])" icon="clipboard" tone="rose" :href="route('admin.follow-ups.index')" hint="People waiting for a caring touch" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Discipleship funnel --}}
            @can('discipleship.view')
                @php $max = max(1, $funnel->max('count')); @endphp
                <div class="card card-pad">
                    <div class="flex items-center justify-between">
                        <h2 class="font-extrabold uppercase">Discipleship journey</h2>
                        <a href="{{ route('admin.journey.index') }}" class="text-sm font-bold text-brand">Open journey →</a>
                    </div>
                    <div class="mt-6 space-y-3">
                        @foreach ($funnel as $row)
                            <a href="{{ route('admin.journey.index', ['stage' => $row['stage']->id]) }}" class="group grid grid-cols-[6.5rem_1fr_3rem] items-center gap-4">
                                <span class="text-sm font-extrabold tracking-wider uppercase">{{ $row['stage']->name }}</span>
                                <span class="h-9 overflow-hidden rounded-xl bg-slate-100">
                                    <span class="flex h-full items-center rounded-xl px-3 text-xs font-bold text-white transition group-hover:opacity-90" style="width: {{ max(6, round($row['count'] / $max * 100)) }}%; background: {{ $row['stage']->color }}"></span>
                                </span>
                                <span class="text-right font-extrabold tabular-nums">{{ $row['count'] }}</span>
                            </a>
                            @unless ($loop->last)<div class="ml-[6.5rem] pl-4 text-slate-300"><x-icon name="chevron-down" class="size-4" /></div>@endunless
                        @endforeach
                    </div>
                </div>
            @endcan

            {{-- Leader: own LifeGroup(s) --}}
            @foreach ($ledGroups as $item)
                <div class="card card-pad">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="eyebrow">My LifeGroup</p>
                            <h2 class="text-xl font-extrabold">{{ $item['group']->name }}</h2>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.meetings.create', $item['group']) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Record meeting</a>
                            <a href="{{ route('admin.lifegroups.show', $item['group']) }}" class="btn btn-outline btn-sm">Open</a>
                        </div>
                    </div>
                    <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ([
                            'Members' => $item['summary']['members'],
                            'Visitors' => $item['summary']['visitors'],
                            'Being discipled' => $item['summary']['being_discipled'],
                            'Disciplers' => $item['summary']['disciplers'],
                            'Potential leaders' => $item['summary']['potential_leaders'],
                            'Attendance' => $item['summary']['attendance_rate'] !== null ? $item['summary']['attendance_rate'].'%' : '—',
                        ] as $label => $value)
                            <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[0.65rem] font-bold tracking-wider text-muted uppercase">{{ $label }}</dt><dd class="mt-1 text-xl font-extrabold">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endforeach

            {{-- Needs follow-up --}}
            <div class="card">
                <div class="flex items-center justify-between border-b border-line p-5">
                    <h2 class="flex items-center gap-2 font-extrabold uppercase"><x-icon name="heart" class="size-5 text-brand" /> Needs follow-up</h2>
                    <a href="{{ route('admin.follow-ups.index') }}" class="text-sm font-bold text-brand">All follow-ups →</a>
                </div>
                @forelse ($care as $section)
                    <div class="border-b border-line p-5 last:border-b-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-bold">{{ $section['label'] }} <span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand">{{ $section['count'] }}</span></p>
                                <p class="text-xs text-muted">{{ $section['hint'] }}</p>
                            </div>
                            <a href="{{ $section['url'] }}" class="text-xs font-bold text-brand">View all</a>
                        </div>
                        <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($section['items'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="flex items-center gap-3 rounded-xl border border-line px-3 py-2 hover:border-brand/40">
                                        <x-avatar :name="$item['title']" size="size-8" />
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold">{{ $item['title'] }}</span>
                                            <span @class(['block truncate text-xs', 'font-semibold text-amber-600' => $item['urgent'], 'text-muted' => ! $item['urgent']])>{{ $item['meta'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <x-empty title="Everyone is cared for 🙌" icon="check-circle">Tidak ada yang menunggu follow-up saat ini.</x-empty>
                @endforelse
            </div>

            {{-- Ministry coordinator --}}
            @if ($ministries->isNotEmpty())
                <div class="card">
                    <div class="flex items-center justify-between border-b border-line p-5">
                        <h2 class="font-extrabold uppercase">My ministries</h2>
                        <a href="{{ route('admin.volunteer-applications.index') }}" class="text-sm font-bold text-brand">Applications →</a>
                    </div>
                    <div class="grid gap-3 p-5 sm:grid-cols-2">
                        @foreach ($ministries as $ministry)
                            <a href="{{ route('admin.ministries.show', $ministry) }}" class="rounded-2xl border border-line p-4 hover:border-brand/40">
                                <p class="font-extrabold">{{ $ministry->name }}</p>
                                <p class="mt-1 text-sm text-muted">{{ $ministry->active_members_count }} active volunteers · {{ $ministry->waiting_count }} waiting</p>
                            </a>
                        @endforeach
                    </div>
                    @if ($serving->isNotEmpty())
                        <div class="border-t border-line p-5">
                            <p class="text-sm font-bold">Serving in the next 14 days</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($serving as $slot)
                                    <li class="flex justify-between gap-2"><span>{{ $slot->profile->displayName() }} · {{ $slot->ministry->name }}</span><span class="text-muted">{{ $slot->serve_date->translatedFormat('D, j M') }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @can('birthdays.view')
                @livewire(\App\Livewire\BirthdayWidget::class, ['limit' => 6])
            @endcan

            @if ($isCampus)
                <div class="card card-pad">
                    <p class="eyebrow">Campus ministry</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $campusStudents }}</p>
                    <p class="text-sm text-muted">students in your campuses</p>
                    <a href="{{ route('admin.campuses.index') }}" class="btn btn-outline btn-sm mt-4">Open campus ministry</a>
                </div>
            @endif

            @if ($recent->isNotEmpty())
                <div class="card">
                    <div class="flex items-center justify-between border-b border-line p-5">
                        <h2 class="font-extrabold uppercase">Just connected</h2>
                        <a href="{{ route('admin.involvement.index') }}" class="text-sm font-bold text-brand">All →</a>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($recent as $request)
                            <li>
                                <a href="{{ route('admin.involvement.show', $request) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                                    <x-avatar :name="$request->full_name" size="size-9" />
                                    <span class="min-w-0 grow">
                                        <span class="block truncate text-sm font-bold">{{ $request->full_name }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $request->interests->pluck('name')->take(2)->implode(', ') ?: $request->type->label() }}</span>
                                    </span>
                                    <x-badge :value="$request->status" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($classes->isNotEmpty())
                <div class="card">
                    <div class="flex items-center justify-between border-b border-line p-5">
                        <h2 class="font-extrabold uppercase">Upcoming classes</h2>
                        <a href="{{ route('admin.classes.index') }}" class="text-sm font-bold text-brand">All →</a>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($classes as $batch)
                            <li>
                                <a href="{{ route('admin.classes.show', $batch) }}" class="block px-5 py-3 hover:bg-slate-50">
                                    <p class="text-sm font-bold">{{ $batch->program->name }}</p>
                                    <p class="text-xs text-muted">{{ $batch->name }} · {{ $batch->start_date?->translatedFormat('j M') }} · {{ $batch->active_participants_count }}{{ $batch->capacity ? '/'.$batch->capacity : '' }} participants</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
