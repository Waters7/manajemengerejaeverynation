<x-layouts.admin :title="$batch->fullLabel()">
    <x-page-header :title="$batch->program->name" :eyebrow="$batch->name" :back="route('admin.classes.index')"
        :description="trim(($batch->start_date?->translatedFormat('j M Y') ?? '').($batch->end_date ? ' – '.$batch->end_date->translatedFormat('j M Y') : '').($batch->location ? ' · '.$batch->location : '').($batch->facilitator ? ' · '.$batch->facilitator->full_name : ''))">
        <x-badge :value="$batch->status" />
        <x-badge :color="$batch->registration_status === 'open' ? 'green' : 'gray'">Registration {{ $batch->registration_status }}</x-badge>
        @if ($canManage)<a href="{{ route('admin.classes.edit', $batch) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>@endif
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5">
                <h2 class="font-extrabold uppercase">Participants ({{ $participants->where('status', '!=', \App\Enums\ParticipantStatus::Cancelled)->count() }}{{ $batch->capacity ? ' / '.$batch->capacity : '' }})</h2>
                @if ($canManage)
                    <x-modal name="enroll" title="Register participants">
                        <x-slot:trigger><button type="button" class="btn btn-primary btn-sm"><x-icon name="user-plus" class="size-4" /> Register people</button></x-slot:trigger>
                        <form method="POST" action="{{ route('admin.classes.participants.store', $batch) }}" class="space-y-4" x-data="{ q: '' }">
                            @csrf
                            <input x-model="q" type="search" class="input" placeholder="Filter names…">
                            <div class="max-h-80 space-y-1 overflow-y-auto">
                                @foreach ($candidates as $id => $name)
                                    <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50" x-show="'{{ Str::lower(addslashes($name)) }}'.includes(q.toLowerCase())">
                                        <input type="checkbox" name="profile_ids[]" value="{{ $id }}" class="checkbox"> {{ $name }}
                                    </label>
                                @endforeach
                            </div>
                            <button class="btn btn-primary w-full">Register selected</button>
                        </form>
                    </x-modal>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Name</th><th>LifeGroup</th><th>Attendance</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($participants as $participant)
                            @php $present = $participant->attendances->where('status', \App\Enums\AttendanceStatus::Present)->count(); @endphp
                            <tr>
                                <td><a href="{{ route('admin.members.show', $participant->profile) }}" class="flex items-center gap-2 font-bold hover:text-brand"><x-avatar :profile="$participant->profile" size="size-8" /> {{ $participant->profile->full_name }}</a></td>
                                <td class="text-sm">{{ $participant->profile->activeLifeGroups->first()?->name ?? '—' }}</td>
                                <td class="min-w-36"><x-progress :value="$present" :total="max(1, $batch->sessions->count())" /></td>
                                <td>
                                    @if ($canManage)
                                        <form method="POST" action="{{ route('admin.classes.participants.update', $participant) }}">@csrf @method('PATCH')
                                            <select name="status" class="input !w-36 !py-1 text-xs" onchange="this.form.submit()">
                                                @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($participant->status->value === $value)>{{ $label }}</option>@endforeach
                                            </select>
                                        </form>
                                    @else
                                        <x-badge :value="$participant->status" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty title="No participants yet" icon="users" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canManage)
                <p class="border-t border-line px-5 py-3 text-xs text-muted">Setting a participant to <strong>Completed</strong> completes the program in their discipleship journey.</p>
            @endif
        </div>

        <div class="card h-fit">
            <div class="flex items-center justify-between border-b border-line p-5">
                <h2 class="font-extrabold uppercase">Sessions</h2>
                @if ($canManage && $batch->sessions->isEmpty())
                    <form method="POST" action="{{ route('admin.classes.sessions.generate', $batch) }}">@csrf<button class="btn btn-outline btn-sm">Generate</button></form>
                @endif
            </div>
            <ul class="divide-y divide-line">
                @forelse ($batch->sessions as $session)
                    <li>
                        <a href="{{ route('admin.classes.sessions.show', $session) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                            <span><strong>{{ $session->session_number }}. {{ $session->topic }}</strong><span class="block text-xs text-muted">{{ $session->session_date?->translatedFormat('D, j M Y') ?? 'Date TBA' }}{{ $session->start_time ? ' · '.substr($session->start_time, 0, 5) : '' }}{{ $session->room ? ' · '.$session->room : '' }}</span></span>
                            <span class="text-sm font-bold tabular-nums">{{ $session->present_count }}</span>
                        </a>
                    </li>
                @empty
                    <li class="p-5 text-sm text-muted">No sessions yet.</li>
                @endforelse
            </ul>
            @if ($canManage)
                <form method="POST" action="{{ route('admin.classes.sessions.store', $batch) }}" class="space-y-3 border-t border-line p-5">
                    @csrf
                    <p class="text-sm font-bold">Add session</p>
                    <x-form.input name="topic" placeholder="Topic" required />
                    <div class="grid grid-cols-2 gap-2">
                        <x-form.input name="session_date" type="date" />
                        <x-form.input name="start_time" type="time" />
                    </div>
                    <x-form.input name="room" placeholder="Room" />
                    <button class="btn btn-dark btn-sm w-full">Add session</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.admin>
