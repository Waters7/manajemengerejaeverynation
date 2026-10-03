<x-layouts.admin title="Serving Schedule">
    <x-page-header title="Serving schedule" :description="$ministry ? $ministry->name.' · next 4 weeks from '.$weekStart->translatedFormat('j M Y') : 'No ministries assigned to you.'" />

    @if ($ministries->isNotEmpty())
        <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
            <x-form.select name="ministry" label="Ministry" :options="$ministries->pluck('name', 'id')" :value="$ministry?->id" />
            <x-form.input name="week" type="date" label="From week" :value="$weekStart" />
            <button class="btn btn-dark btn-sm">Show</button>
        </form>
    @endif

    @if ($ministry)
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                @forelse ($slots as $date => $daySlots)
                    <div class="card">
                        <h2 class="border-b border-line px-5 py-3 font-extrabold">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, j F Y') }}</h2>
                        <ul class="divide-y divide-line">
                            @foreach ($daySlots as $slot)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                                    <span class="flex items-center gap-3"><x-avatar :profile="$slot->profile" size="size-8" /><span><span class="block font-bold">{{ $slot->profile->full_name }}</span><span class="text-xs text-muted">{{ $slot->role?->name ?? 'Team' }}{{ $slot->service_label ? ' · '.$slot->service_label : '' }}</span></span></span>
                                    @if ($canManage)
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.serving.update', $slot) }}">@csrf @method('PATCH')
                                                <select name="status" class="input !w-36 !py-1 text-xs" onchange="this.form.submit()">@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($slot->status === $value)>{{ $label }}</option>@endforeach</select>
                                            </form>
                                            <form method="POST" action="{{ route('admin.serving.destroy', $slot) }}">@csrf @method('DELETE')<button class="text-muted hover:text-danger"><x-icon name="x" class="size-4" /></button></form>
                                        </div>
                                    @else
                                        <x-badge>{{ $statuses[$slot->status] ?? $slot->status }}</x-badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="card"><x-empty title="Nothing scheduled in these weeks" icon="calendar" /></div>
                @endforelse
            </div>

            @if ($canManage)
                <form method="POST" action="{{ route('admin.serving.store') }}" class="card card-pad h-fit space-y-4">
                    @csrf
                    <input type="hidden" name="ministry_id" value="{{ $ministry->id }}">
                    <h2 class="font-extrabold uppercase">Schedule a volunteer</h2>
                    <x-form.select name="profile_id" label="Volunteer" :options="$ministry->activeMembers->pluck('profile.full_name', 'profile_id')" placeholder="Active volunteers…" required />
                    <x-form.select name="ministry_role_id" label="Role" :options="$ministry->roles->pluck('name', 'id')" placeholder="—" />
                    <x-form.input name="serve_date" type="date" label="Date" :value="today()->next('Sunday')" required />
                    <x-form.input name="service_label" label="Service" placeholder="e.g. Sunday 10.00" />
                    <button class="btn btn-primary w-full">Add to schedule</button>
                </form>
            @endif
        </div>
    @endif
</x-layouts.admin>
