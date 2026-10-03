<x-layouts.member title="My LifeGroup">
    <x-page-header title="My LifeGroup">
        <a href="{{ route('lifegroups.index') }}" class="btn btn-outline btn-sm">Browse LifeGroups</a>
    </x-page-header>

    @forelse ($groups as $group)
        <div class="card overflow-hidden">
            <div class="flex flex-col gap-4 bg-brand p-6 text-white sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div>
                    <p class="text-xs font-bold tracking-[0.2em] text-white/70 uppercase">{{ $group->category->label() }}</p>
                    <h2 class="mt-1 text-2xl font-extrabold">{{ $group->name }}</h2>
                    <p class="mt-1 text-white/80">{{ $group->scheduleLabel() }} · {{ $group->location ?? $group->area }}</p>
                </div>
                @if ($group->whatsapp_invite_url)
                    <a href="{{ $group->whatsapp_invite_url }}" target="_blank" rel="noopener" class="btn btn-whatsapp"><x-icon name="whatsapp" class="size-4" /> Group chat</a>
                @endif
            </div>
            <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-2">
                <div>
                    <p class="eyebrow">Leaders</p>
                    <div class="mt-3 flex flex-wrap gap-4">
                        @foreach (array_filter([$group->leader, $group->coLeader]) as $leader)
                            <div class="flex items-center gap-3"><x-avatar :profile="$leader" /><span class="font-bold">{{ $leader->displayName() }}</span></div>
                        @endforeach
                    </div>
                    <p class="eyebrow mt-8">Members ({{ $group->activeMembers->count() }})</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($group->activeMembers as $member)
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 py-1 pr-3 pl-1 text-sm font-semibold"><x-avatar :profile="$member" size="size-6" /> {{ $member->displayName() }}</span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="eyebrow">Recent gatherings</p>
                    <ul class="mt-3 divide-y divide-line">
                        @forelse ($meetings->where('life_group_id', $group->id) as $meeting)
                            @php $mine = $meeting->attendances->first(); @endphp
                            <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                <span><strong>{{ $meeting->meeting_date->translatedFormat('j M') }}</strong> · {{ $meeting->topic }}</span>
                                @if ($mine)<x-badge :value="$mine->status" />@endif
                            </li>
                        @empty
                            <li class="py-2 text-sm text-muted">Belum ada catatan pertemuan.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><x-empty title="You're not in a LifeGroup yet" icon="users">
            LifeGroup adalah tempat terbaik untuk bertumbuh bersama. <a href="{{ route('lifegroups.index') }}" class="font-bold text-brand">Temukan LifeGroup →</a>
        </x-empty></div>
    @endforelse

    @if ($requests->isNotEmpty())
        <h2 class="mt-10 mb-3 font-extrabold uppercase">My join requests</h2>
        <div class="card divide-y divide-line">
            @foreach ($requests as $request)
                <div class="flex items-center justify-between gap-3 p-4">
                    <span class="font-semibold">{{ $request->lifeGroup?->name }} <span class="text-sm font-normal text-muted">· {{ $request->created_at->translatedFormat('j M Y') }}</span></span>
                    <x-badge :value="$request->status" />
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.member>
