<x-layouts.admin :title="$profile->full_name">
    @php
        $tabs = ['overview' => 'Overview', 'discipleship' => 'Discipleship', 'lifegroup' => 'LifeGroup', 'classes' => 'Classes', 'ministry' => 'Ministry', 'events' => 'Events', 'attendance' => 'Attendance', 'timeline' => 'Timeline'];
        $canEdit = auth()->user()->can('update', $profile);
        $canDisciple = auth()->user()->can('disciple', $profile);
    @endphp

    <div class="card mb-6 overflow-hidden">
        <div class="h-24 bg-gradient-to-r from-brand to-brand-800"></div>
        <div class="flex flex-col gap-4 px-6 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="-mt-10 flex items-end gap-4">
                <x-avatar :profile="$profile" size="size-24" class="text-2xl ring-4" />
                <div class="pb-1">
                    <h1 class="text-2xl font-extrabold tracking-tight">{{ $profile->full_name }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted">
                        @if ($profile->nickname)<span>“{{ $profile->nickname }}”</span>@endif
                        <x-badge :value="$profile->member_status" />
                        @if ($profile->currentStage)<x-badge color="blue">{{ $profile->currentStage->name }}</x-badge>@endif
                        @if ($profile->leadershipCandidate)<x-badge :value="$profile->leadershipCandidate->stage" />@endif
                        @if ($profile->user)<span class="inline-flex items-center gap-1 text-xs"><x-icon name="key" class="size-3.5" /> {{ $profile->user->roles->pluck('name')->map(fn ($r) => \App\Enums\Role::tryFrom($r)?->label() ?? $r)->implode(', ') }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-wa-button :href="$waLink" label="WhatsApp" />
                @if ($canEdit)
                    <a href="{{ route('admin.members.edit', $profile) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
                    @if ($profile->member_status !== \App\Enums\MemberStatus::Member)
                        <form method="POST" action="{{ route('admin.members.activate', $profile) }}" data-confirm="Mark {{ $profile->displayName() }} as an active member?">@csrf<button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Activate member</button></form>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div x-data="{ tab: (location.hash || '#overview').slice(1) }" x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))">
        <nav class="mb-6 flex gap-1 overflow-x-auto rounded-2xl border border-line bg-white p-1.5">
            @foreach ($tabs as $key => $label)
                <button type="button" x-on:click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-brand text-white' : 'text-slate-600 hover:bg-slate-100'" class="shrink-0 rounded-xl px-4 py-2 text-sm font-bold transition">{{ $label }}</button>
            @endforeach
        </nav>

        {{-- OVERVIEW --}}
        <div x-show="tab === 'overview'" class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="card card-pad">
                    <h2 class="font-extrabold uppercase">Profile</h2>
                    <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                        @foreach ([
                            'WhatsApp' => \App\Services\WhatsApp::display($profile->whatsapp),
                            'Email' => $profile->email,
                            'Gender' => $profile->gender?->label(),
                            'Birth date' => $profile->birth_date ? $profile->birth_date->translatedFormat('j F Y').' ('.$profile->age().')' : null,
                            'Area' => $profile->area,
                            'Address' => $profile->address,
                            'Occupation' => $profile->occupation,
                            'Company' => $profile->company,
                            'Campus' => $profile->campus?->name ?? $profile->school_name,
                            'Life stage' => $profile->life_stage?->label(),
                            'First visit' => $profile->first_visit_date?->translatedFormat('j M Y'),
                            'Join date' => $profile->join_date?->translatedFormat('j M Y'),
                            'Source' => $profile->source ? (\App\Enums\DiscoverySource::tryFrom($profile->source)?->label() ?? $profile->source) : null,
                            'LifeGroup' => $profile->lifeGroupMemberships->where('status', 'active')->map(fn ($m) => $m->lifeGroup?->name)->filter()->implode(', '),
                            'Discipler' => $profile->activeDiscipler?->discipler?->full_name,
                            'Current stage' => $profile->currentStage?->name,
                            'Current program' => $profile->currentProgram?->name,
                            'Ministry' => $profile->ministryMemberships->map(fn ($m) => $m->ministry->name)->implode(', '),
                        ] as $term => $detail)
                            <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">{{ $term }}</dt><dd class="mt-0.5 font-semibold text-ink">{{ filled($detail) ? $detail : '—' }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($profile->newcomer)
                        <div class="mt-6 rounded-2xl bg-soft p-4 text-sm">
                            <p class="font-bold">Newcomer journey: <x-badge :value="$profile->newcomer->journey_status" /></p>
                            <p class="mt-1 text-muted">Follow-up: {{ $profile->newcomer->assignee?->name ?? 'not assigned' }}</p>
                        </div>
                    @endif
                </div>

                @can('viewInternalNotes', $profile)
                    <div class="card card-pad">
                        <div class="flex items-center justify-between">
                            <h2 class="font-extrabold uppercase">Follow-up notes</h2>
                            <span class="inline-flex items-center gap-1 text-xs text-muted"><x-icon name="lock" class="size-3.5" /> Internal — never visible to the member</span>
                        </div>
                        <form method="POST" action="{{ route('admin.members.notes.store', $profile) }}" class="mt-4 grid gap-3 sm:grid-cols-[10rem_1fr_auto] sm:items-start">
                            @csrf
                            <x-form.select name="type" :options="\App\Enums\ContactType::options()" value="note" />
                            <x-form.textarea name="body" rows="2" placeholder="What happened? What's the next step?" required />
                            <button class="btn btn-dark">Add</button>
                        </form>
                        <ul class="mt-6 space-y-4">
                            @forelse ($notes as $note)
                                <li class="flex gap-3">
                                    <x-avatar :name="$note->author?->name ?? 'System'" size="size-8" />
                                    <div class="min-w-0 grow rounded-2xl bg-slate-50 px-4 py-3">
                                        <p class="text-xs text-muted"><strong class="text-ink">{{ $note->author?->name ?? 'System' }}</strong> · {{ $note->type->label() }} · {{ $note->created_at->diffForHumans() }}</p>
                                        <p class="mt-1 text-sm whitespace-pre-line">{{ $note->body }}</p>
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-muted">No notes yet.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcan
            </div>

            <div class="space-y-6">
                @if ($canEdit && $availableGroups->isNotEmpty())
                    <form method="POST" action="{{ route('admin.members.lifegroup', $profile) }}" class="card card-pad space-y-3">
                        @csrf
                        <h2 class="font-extrabold uppercase">Assign LifeGroup</h2>
                        <x-form.select name="life_group_id" :options="$availableGroups" placeholder="Choose LifeGroup…" required />
                        <x-form.select name="role" :options="$roles" value="member" />
                        <button class="btn btn-primary btn-sm w-full">Add to LifeGroup</button>
                    </form>
                @endif

                @can('discipleship.manage')
                    <form method="POST" action="{{ route('admin.relationships.store') }}" class="card card-pad space-y-3">
                        @csrf
                        <input type="hidden" name="disciple_profile_id" value="{{ $profile->id }}">
                        <h2 class="font-extrabold uppercase">{{ $profile->activeDiscipler ? 'Change discipler' : 'Assign discipler' }}</h2>
                        <x-form.select name="discipler_profile_id" :options="$disciplerOptions" :value="$profile->activeDiscipler?->discipler_profile_id" placeholder="Choose discipler…" required />
                        <button class="btn btn-outline btn-sm w-full">Save discipler</button>
                    </form>
                @endcan

                @can('followups.manage')
                    <form method="POST" action="{{ route('admin.follow-ups.store') }}" class="card card-pad space-y-3">
                        @csrf
                        <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                        <h2 class="font-extrabold uppercase">Schedule follow-up</h2>
                        <x-form.input name="title" placeholder="e.g. Coffee & check in" required />
                        <x-form.select name="assigned_to" :options="$teamMembers" :value="auth()->id()" />
                        <x-form.input name="due_date" type="date" :value="today()->addDays(3)" />
                        <input type="hidden" name="category" value="general">
                        <button class="btn btn-outline btn-sm w-full">Create task</button>
                    </form>
                @endcan

                @if ($tasks->isNotEmpty())
                    <div class="card card-pad">
                        <h2 class="font-extrabold uppercase">Follow-up tasks</h2>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($tasks as $task)
                                <li class="flex items-start justify-between gap-2">
                                    <span><strong>{{ $task->title }}</strong><br><span class="text-xs text-muted">{{ $task->assignee?->name ?? 'Unassigned' }} · {{ $task->due_date?->translatedFormat('j M') ?? 'no due date' }}</span></span>
                                    <x-badge :value="$task->status" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @can('leadership.manage')
                    @unless ($profile->leadershipCandidate)
                        <form method="POST" action="{{ route('admin.leadership.store') }}" class="card card-pad space-y-3">
                            @csrf
                            <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                            <h2 class="font-extrabold uppercase">Leadership</h2>
                            <x-form.textarea name="recommendation" rows="2" placeholder="Why is this person ready to grow as a leader?" />
                            <button class="btn btn-outline btn-sm w-full">Recommend as potential leader</button>
                        </form>
                    @endunless
                @endcan

                @if ($canEdit && ! $profile->user && $profile->email)
                    <form method="POST" action="{{ route('admin.members.account', $profile) }}" class="card card-pad" data-confirm="Create a login account (role USER) for {{ $profile->email }}?">
                        @csrf
                        <h2 class="font-extrabold uppercase">Login account</h2>
                        <p class="mt-1 text-sm text-muted">No account yet. Create one with role USER and email a set-password link.</p>
                        <button class="btn btn-outline btn-sm mt-3 w-full"><x-icon name="key" class="size-4" /> Create account</button>
                    </form>
                @endif

                @can('delete', $profile)
                    <form method="POST" action="{{ route('admin.members.destroy', $profile) }}" data-confirm="Archive this person? They can be restored from the database.">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm w-full text-danger"><x-icon name="archive" class="size-4" /> Archive person</button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- DISCIPLESHIP --}}
        <div x-show="tab === 'discipleship'" x-cloak class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @if ($canDisciple)
                    @foreach ($activeProgress as $progress)
                        @livewire(\App\Livewire\ProgramTracker::class, ['progress' => $progress], key('tracker-'.$progress->id))
                    @endforeach
                @endif
                <div class="card card-pad">
                    <h2 class="font-extrabold uppercase">4E journey</h2>
                    <div class="mt-4">@include('partials.journey', ['stages' => $stages, 'progressLink' => fn ($p) => route('admin.progress.show', $p)])</div>
                    @if ($canDisciple)
                        <form method="POST" action="{{ route('admin.progress.store', $profile) }}" class="mt-6 grid gap-3 border-t border-line pt-5 sm:grid-cols-4 sm:items-end">
                            @csrf
                            <x-form.select name="program_id" label="Start program" :options="$programs->pluck('name', 'id')" placeholder="Choose…" required class="sm:col-span-2" />
                            <x-form.input name="expected_completion_at" type="date" label="Target" />
                            <button class="btn btn-primary">Start</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="space-y-6">
                <div class="card card-pad">
                    <h2 class="font-extrabold uppercase">Discipler</h2>
                    @if ($profile->activeDiscipler)
                        <a href="{{ route('admin.members.show', $profile->activeDiscipler->discipler) }}" class="mt-3 flex items-center gap-3 font-bold hover:text-brand"><x-avatar :profile="$profile->activeDiscipler->discipler" /> {{ $profile->activeDiscipler->discipler->full_name }}</a>
                        <p class="mt-2 text-xs text-muted">Since {{ $profile->activeDiscipler->started_at?->translatedFormat('j M Y') }}</p>
                        @if ($canDisciple)
                            <form method="POST" action="{{ route('admin.relationships.meetings.store', $profile->activeDiscipler) }}" class="mt-5 space-y-3 border-t border-line pt-4">
                                @csrf
                                <p class="text-sm font-bold">Add meeting</p>
                                <x-form.input name="met_on" type="date" :value="today()" required />
                                <x-form.input name="topic" placeholder="Topic" />
                                <x-form.textarea name="notes" rows="2" placeholder="Notes" />
                                <x-form.input name="next_follow_up_at" type="date" label="Next follow-up" />
                                <button class="btn btn-dark btn-sm w-full">Save meeting</button>
                            </form>
                        @endif
                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($meetings as $meeting)
                                <li class="rounded-xl bg-slate-50 p-2.5"><strong>{{ $meeting->met_on->translatedFormat('j M Y') }}</strong> · {{ $meeting->topic }}@if ($meeting->notes)<br><span class="text-muted">{{ $meeting->notes }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-2 text-sm text-muted">Ready for a discipler — assign one from the Overview tab.</p>
                    @endif
                </div>
                <div class="card card-pad">
                    <h2 class="font-extrabold uppercase">Disciples ({{ $disciples->count() }})</h2>
                    <ul class="mt-3 space-y-2">
                        @forelse ($disciples as $relationship)
                            <li><a href="{{ route('admin.members.show', $relationship->disciple) }}" class="flex items-center gap-2 text-sm font-semibold hover:text-brand"><x-avatar :profile="$relationship->disciple" size="size-7" /> {{ $relationship->disciple->full_name }} <span class="text-xs text-muted">{{ $relationship->disciple->currentStage?->name }}</span></a></li>
                        @empty
                            <li class="text-sm text-muted">Not discipling anyone yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- LIFEGROUP --}}
        <div x-show="tab === 'lifegroup'" x-cloak class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>LifeGroup</th><th>Role</th><th>Status</th><th>Joined</th><th>Left</th></tr></thead>
                <tbody>
                    @forelse ($profile->lifeGroupMemberships as $membership)
                        <tr>
                            <td><a href="{{ route('admin.lifegroups.show', $membership->life_group_id) }}" class="font-bold hover:text-brand">{{ $membership->lifeGroup?->name }}</a></td>
                            <td><x-badge :value="$membership->role" /></td>
                            <td><x-badge :color="$membership->status === 'active' ? 'green' : 'gray'">{{ ucfirst($membership->status) }}</x-badge></td>
                            <td>{{ $membership->joined_at?->translatedFormat('j M Y') }}</td>
                            <td>{{ $membership->left_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty title="Not in a LifeGroup yet" icon="users" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- CLASSES --}}
        <div x-show="tab === 'classes'" x-cloak class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Program</th><th>Batch</th><th>Status</th><th>Attendance</th><th>Completed</th></tr></thead>
                <tbody>
                    @forelse ($profile->classParticipations as $participation)
                        <tr>
                            <td class="font-bold">{{ $participation->batch->program->name }}</td>
                            <td><a href="{{ route('admin.classes.show', $participation->batch) }}" class="hover:text-brand">{{ $participation->batch->name }}</a></td>
                            <td><x-badge :value="$participation->status" /></td>
                            <td>{{ $participation->attendances->where('status', \App\Enums\AttendanceStatus::Present)->count() }} / {{ $participation->attendances->count() }}</td>
                            <td>{{ $participation->completed_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty title="No classes yet" icon="academic" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MINISTRY --}}
        <div x-show="tab === 'ministry'" x-cloak class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Ministry</th><th>Role</th><th>Skills</th><th>Availability</th><th>Joined</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($profile->ministryMemberships as $membership)
                        <tr>
                            <td class="font-bold">{{ $membership->ministry->name }}</td>
                            <td>{{ $membership->role?->name ?? '—' }}</td>
                            <td class="text-sm">{{ implode(', ', $membership->skills ?? []) ?: '—' }}</td>
                            <td class="text-sm">{{ collect($membership->availability ?? [])->map(fn ($a) => \App\Enums\Availability::tryFrom($a)?->label())->filter()->implode(', ') ?: '—' }}</td>
                            <td>{{ $membership->joined_at?->translatedFormat('j M Y') }}</td>
                            <td><x-badge :value="$membership->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty title="Not serving in a ministry yet" icon="hand" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- EVENTS --}}
        <div x-show="tab === 'events'" x-cloak class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Checked in</th></tr></thead>
                <tbody>
                    @forelse ($profile->eventRegistrations->sortByDesc(fn ($r) => $r->event?->starts_at) as $registration)
                        <tr>
                            <td class="font-bold">{{ $registration->event?->title }}</td>
                            <td>{{ $registration->event?->starts_at->translatedFormat('j M Y') }}</td>
                            <td><x-badge :value="$registration->status" /></td>
                            <td>{{ $registration->checked_in_at?->translatedFormat('j M H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty title="No event registrations" icon="calendar" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ATTENDANCE --}}
        <div x-show="tab === 'attendance'" x-cloak class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Date</th><th>LifeGroup</th><th>Topic</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($attendance->sortByDesc(fn ($a) => $a->meeting?->meeting_date) as $row)
                        <tr>
                            <td>{{ $row->meeting?->meeting_date->translatedFormat('j M Y') }}</td>
                            <td>{{ $row->meeting?->lifeGroup?->name }}</td>
                            <td>{{ $row->meeting?->topic }}</td>
                            <td><x-badge :value="$row->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty title="No attendance recorded" icon="calendar" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TIMELINE --}}
        <div x-show="tab === 'timeline'" x-cloak class="card card-pad">
            @include('partials.timeline', ['timeline' => $timeline])
        </div>
    </div>
</x-layouts.admin>
