<x-layouts.admin :title="'Meeting — '.$group->name">
    <x-page-header :title="$meeting->exists ? 'Edit meeting' : 'Record meeting'" :eyebrow="$group->name" :back="route('admin.lifegroups.show', $group)" />

    <form method="POST" action="{{ $meeting->exists ? route('admin.meetings.update', $meeting) : route('admin.meetings.store', $group) }}" class="grid gap-6 lg:grid-cols-3"
        x-data="{ setAll(status) { document.querySelectorAll('[data-att=' + status + ']').forEach(el => el.checked = true) } }">
        @csrf
        @if ($meeting->exists) @method('PUT') @endif

        <div class="card card-pad h-fit space-y-4">
            <x-form.input name="meeting_date" type="date" label="Date" :value="$meeting->meeting_date" required />
            <x-form.input name="topic" label="Topic" :value="$meeting->topic" />
            <x-form.input name="location" label="Location" :value="$meeting->location" />
            <x-form.input name="visitor_count" type="number" label="Visitors (not yet in list)" :value="$meeting->visitor_count ?? 0" min="0" />
            <x-form.textarea name="notes" label="Notes" :value="$meeting->notes" rows="3" />
            <button class="btn btn-primary w-full">Save meeting</button>
        </div>

        <div class="card lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-5">
                <h2 class="font-extrabold uppercase">Attendance</h2>
                <div class="flex gap-2">
                    <button type="button" x-on:click="setAll('present')" class="btn btn-outline btn-sm">All present</button>
                    <button type="button" x-on:click="setAll('absent')" class="btn btn-ghost btn-sm">All absent</button>
                </div>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($members as $membership)
                    @php $value = old('attendance.'.$membership->profile_id, $current[$membership->profile_id] ?? 'present'); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <span class="flex items-center gap-3 font-bold"><x-avatar :profile="$membership->profile" size="size-8" /> {{ $membership->profile->full_name }} <x-badge :value="$membership->role" /></span>
                        <div class="flex gap-1.5">
                            @foreach ($statuses as $status => $label)
                                <label class="chip !px-3 !py-1.5 text-xs">
                                    <input type="radio" name="attendance[{{ $membership->profile_id }}]" value="{{ $status }}" data-att="{{ $status }}" class="sr-only" @checked($value === $status)> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </li>
                @empty
                    <li class="p-5 text-sm text-muted">Add members to this LifeGroup first.</li>
                @endforelse
            </ul>
        </div>
    </form>

    @if ($meeting->exists)
        <form method="POST" action="{{ route('admin.meetings.destroy', $meeting) }}" data-confirm="Delete this meeting and its attendance?" class="mt-4">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /> Delete meeting</button>
        </form>
    @endif
</x-layouts.admin>
