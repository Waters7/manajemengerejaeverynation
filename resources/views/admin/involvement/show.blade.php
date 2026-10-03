<x-layouts.admin :title="$item->full_name">
    @php $canUpdate = auth()->user()->can('update', $item); @endphp
    <x-page-header :title="$item->full_name" :back="route('admin.involvement.index')" :eyebrow="$item->type->label().' · '.$item->created_at->translatedFormat('j F Y, H:i')">
        <x-wa-button :href="$waLink" label="Contact via WhatsApp" size="" />
        @if ($item->profile)
            <a href="{{ route('admin.members.show', $item->profile) }}" class="btn btn-outline btn-sm"><x-icon name="user" class="size-4" /> Person profile</a>
        @endif
    </x-page-header>

    {{-- Workflow --}}
    <div class="card mb-6 p-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($statuses as $value => $label)
                @php $active = $item->status->value === $value; @endphp
                @if ($canUpdate && ! $active)
                    <form method="POST" action="{{ route('admin.involvement.status', $item) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $value }}">
                        <button class="rounded-full border border-line px-4 py-2 text-xs font-bold tracking-wide text-slate-600 uppercase hover:border-brand hover:text-brand">{{ $label }}</button>
                    </form>
                @else
                    <span @class(['rounded-full px-4 py-2 text-xs font-bold tracking-wide uppercase', 'bg-brand text-white' => $active, 'border border-line text-slate-400' => ! $active])>{{ $label }}</span>
                @endif
                @if ($loop->index < 4)<x-icon name="chevron-right" class="size-4 text-slate-300" />@endif
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Details</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                    @foreach ([
                        'Nickname' => $item->nickname,
                        'WhatsApp' => \App\Services\WhatsApp::display($item->whatsapp),
                        'Email' => $item->email,
                        'Gender' => $item->gender?->label(),
                        'Birth date' => $item->birth_date ? $item->birth_date->translatedFormat('j M Y').' ('.$item->age().')' : null,
                        'Area' => $item->area,
                        'Occupation' => $item->occupation,
                        'Company' => $item->company,
                        'Campus' => $item->campus?->name ?? $item->campus_name,
                        'Status' => $item->life_stage?->label(),
                        'Found us via' => $item->source?->label().($item->source_other ? ' — '.$item->source_other : ''),
                        'Current LifeGroup' => $item->profile?->activeLifeGroups->pluck('name')->implode(', '),
                    ] as $term => $detail)
                        <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">{{ $term }}</dt><dd class="mt-0.5 font-semibold">{{ filled($detail) ? $detail : '—' }}</dd></div>
                    @endforeach
                </dl>

                <h3 class="mt-8 text-xs font-bold tracking-wider text-muted uppercase">Saya tertarik untuk…</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($item->interests as $interest)
                        <span class="badge badge-blue !text-xs !normal-case">{{ $interest->name }}</span>
                    @empty
                        <span class="text-sm text-muted">—</span>
                    @endforelse
                </div>

                @if ($item->ministries->isNotEmpty() || $item->experience || $item->motivation)
                    <div class="mt-8 rounded-2xl bg-soft p-5">
                        <h3 class="font-extrabold">Ministry interest</h3>
                        <p class="mt-1 text-sm">{{ $item->ministries->pluck('name')->implode(', ') ?: '—' }}</p>
                        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs font-bold text-muted uppercase">Skills</dt><dd>{{ implode(', ', $item->skills ?? []) ?: '—' }}</dd></div>
                            <div><dt class="text-xs font-bold text-muted uppercase">Availability</dt><dd>{{ collect($item->availability ?? [])->map(fn ($a) => \App\Enums\Availability::tryFrom($a)?->label())->filter()->implode(', ') ?: '—' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs font-bold text-muted uppercase">Experience</dt><dd class="whitespace-pre-line">{{ $item->experience ?: '—' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs font-bold text-muted uppercase">Kenapa ingin terlibat?</dt><dd class="whitespace-pre-line">{{ $item->motivation ?: '—' }}</dd></div>
                        </dl>
                        @foreach ($item->volunteerApplications as $application)
                            <a href="{{ route('admin.volunteer-applications.show', $application) }}" class="mt-3 inline-flex items-center gap-1 text-sm font-bold text-brand">Volunteer application · {{ $application->status->label() }} →</a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="font-extrabold uppercase">Follow-up notes</h2>
                    <span class="inline-flex items-center gap-1 text-xs text-muted"><x-icon name="lock" class="size-3.5" /> Internal</span>
                </div>
                @if ($canUpdate)
                    <form method="POST" action="{{ route('admin.involvement.notes.store', $item) }}" class="mt-4 grid gap-3 sm:grid-cols-[10rem_1fr_auto] sm:items-start">
                        @csrf
                        <x-form.select name="type" :options="$contactTypes" value="whatsapp" />
                        <x-form.textarea name="body" rows="2" placeholder="e.g. Sent a WhatsApp, will meet after Sunday Service" required />
                        <button class="btn btn-dark">Add</button>
                    </form>
                @endif
                <ul class="mt-6 space-y-4">
                    @forelse ($item->notes as $note)
                        <li class="flex gap-3">
                            <x-avatar :name="$note->author?->name ?? 'System'" size="size-8" />
                            <div class="grow rounded-2xl bg-slate-50 px-4 py-3">
                                <p class="text-xs text-muted"><strong class="text-ink">{{ $note->author?->name }}</strong> · {{ $note->type->label() }} · {{ $note->created_at->diffForHumans() }}</p>
                                <p class="mt-1 text-sm whitespace-pre-line">{{ $note->body }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-muted">No notes yet. Say hi via WhatsApp and record it here.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            @can('involvement.manage')
                <form method="POST" action="{{ route('admin.involvement.assign', $item) }}" class="card card-pad space-y-3">
                    @csrf
                    <h2 class="font-extrabold uppercase">Assign follow-up</h2>
                    @if ($item->assignee)
                        <p class="text-sm">Currently: <strong>{{ $item->assignee->name }}</strong>@if ($item->due_date) · due {{ $item->due_date->translatedFormat('j M Y') }}@endif</p>
                    @endif
                    <select name="assigned_to" class="input" required>
                        <option value="">Choose person…</option>
                        @foreach ($team as $member)
                            <option value="{{ $member->id }}" @selected($item->assigned_to === $member->id)>{{ $member->name }} — {{ $member->primaryRole()?->label() }}</option>
                        @endforeach
                    </select>
                    <x-form.input name="due_date" type="date" label="Due" :value="$item->due_date ?? today()->addDays(3)" />
                    <button class="btn btn-primary btn-sm w-full">Assign</button>
                </form>
            @endcan

            @if ($canUpdate && $item->profile)
                <form method="POST" action="{{ route('admin.involvement.lifegroup', $item) }}" class="card card-pad space-y-3">
                    @csrf
                    <h2 class="font-extrabold uppercase">Assign LifeGroup</h2>
                    <x-form.select name="life_group_id" :options="$lifeGroups" placeholder="Choose LifeGroup…" required />
                    <button class="btn btn-outline btn-sm w-full">Add to LifeGroup</button>
                </form>

                <form method="POST" action="{{ route('admin.involvement.one2one', $item) }}" class="card card-pad space-y-3">
                    @csrf
                    <h2 class="font-extrabold uppercase">Start One 2 One</h2>
                    <x-form.select name="discipler_profile_id" :options="$disciplers" :value="$item->profile->activeDiscipler?->discipler_profile_id" placeholder="Choose discipler…" />
                    <button class="btn btn-outline btn-sm w-full">Start One 2 One</button>
                </form>

                <form method="POST" action="{{ route('admin.involvement.ministry', $item) }}" class="card card-pad space-y-3">
                    @csrf
                    <h2 class="font-extrabold uppercase">Assign ministry</h2>
                    <x-form.select name="ministry_id" :options="$ministries" :value="$item->ministries->first()?->id" placeholder="Choose ministry…" required />
                    <p class="text-xs text-muted">Adds them to orientation. This never grants a system role.</p>
                    <button class="btn btn-outline btn-sm w-full">Assign ministry</button>
                </form>

                <div class="card card-pad space-y-2">
                    @if ($item->status !== \App\Enums\InvolvementStatus::Active)
                        <form method="POST" action="{{ route('admin.involvement.activate', $item) }}" data-confirm="Activate {{ $item->displayName() }} as an active member?">@csrf<button class="btn btn-success btn-sm w-full"><x-icon name="check" class="size-4" /> Activate member</button></form>
                    @endif
                    @if ($item->archived_at)
                        <form method="POST" action="{{ route('admin.involvement.restore', $item) }}">@csrf<button class="btn btn-ghost btn-sm w-full">Restore request</button></form>
                    @else
                        <form method="POST" action="{{ route('admin.involvement.archive', $item) }}" data-confirm="Archive this request?">@csrf<button class="btn btn-ghost btn-sm w-full"><x-icon name="archive" class="size-4" /> Archive request</button></form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
