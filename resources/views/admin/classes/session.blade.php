<x-layouts.admin :title="'Session '.$session->session_number">
    <x-page-header :title="$session->session_number.'. '.$session->topic" :eyebrow="$session->batch->fullLabel()" :back="route('admin.classes.show', $session->batch)" />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.classes.sessions.attendance', $session) }}" class="card lg:col-span-2"
            x-data="{ setAll(status) { document.querySelectorAll('[data-att=' + status + ']').forEach(el => el.checked = true) } }">
            @csrf @method('PUT')
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-5">
                <h2 class="font-extrabold uppercase">Attendance</h2>
                @if ($canManage)
                    <div class="flex gap-2">
                        <button type="button" x-on:click="setAll('present')" class="btn btn-outline btn-sm">All present</button>
                        <button class="btn btn-primary btn-sm">Save attendance</button>
                    </div>
                @endif
            </div>
            <fieldset @disabled(! $canManage)>
                <ul class="divide-y divide-line">
                    @forelse ($participants as $participant)
                        @php $value = $current[$participant->id] ?? null; @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                            <span class="flex items-center gap-3 font-bold"><x-avatar :profile="$participant->profile" size="size-8" /> {{ $participant->profile->full_name }}</span>
                            <div class="flex gap-1.5">
                                @foreach ($statuses as $status => $label)
                                    <label class="chip !px-3 !py-1.5 text-xs"><input type="radio" name="attendance[{{ $participant->id }}]" value="{{ $status }}" data-att="{{ $status }}" class="sr-only" @checked($value === $status)> {{ $label }}</label>
                                @endforeach
                            </div>
                        </li>
                    @empty
                        <li class="p-5 text-sm text-muted">No participants registered.</li>
                    @endforelse
                </ul>
            </fieldset>
        </form>

        @if ($canManage)
            <div class="space-y-4">
                <form method="POST" action="{{ route('admin.classes.sessions.update', $session) }}" class="card card-pad space-y-4">
                    @csrf @method('PUT')
                    <h2 class="font-extrabold uppercase">Session details</h2>
                    <x-form.input name="session_number" type="number" label="Number" :value="$session->session_number" />
                    <x-form.input name="topic" label="Topic" :value="$session->topic" required />
                    <x-form.input name="session_date" type="date" label="Date" :value="$session->session_date" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-form.input name="start_time" type="time" label="Start" :value="$session->start_time ? substr($session->start_time, 0, 5) : null" />
                        <x-form.input name="end_time" type="time" label="End" :value="$session->end_time ? substr($session->end_time, 0, 5) : null" />
                    </div>
                    <x-form.select name="facilitator_profile_id" label="Facilitator" :options="$facilitators" :value="$session->facilitator_profile_id" placeholder="—" />
                    <x-form.input name="room" label="Room" :value="$session->room" />
                    <button class="btn btn-dark w-full">Save session</button>
                </form>
                <form method="POST" action="{{ route('admin.classes.sessions.destroy', $session) }}" data-confirm="Remove this session and its attendance?">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm w-full text-danger"><x-icon name="trash" class="size-4" /> Remove session</button>
                </form>
            </div>
        @endif
    </div>
</x-layouts.admin>
