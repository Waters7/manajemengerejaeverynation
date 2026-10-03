<a href="{{ route('lifegroups.show', $group->slug) }}" class="group card card-hover flex flex-col overflow-hidden">
    <div class="relative aspect-[16/9] overflow-hidden">
        @if ($group->coverUrl())
            <img src="{{ $group->coverUrl() }}" alt="" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="flex size-full items-end bg-gradient-to-br from-brand to-brand-800 p-5">
                <x-icon name="users" class="size-10 text-white/40" />
            </div>
        @endif
        <span class="absolute top-4 left-4 badge bg-white/95 text-ink">{{ $group->category->label() }}</span>
        @if ($group->accepting_members)
            <span class="absolute top-4 right-4 badge bg-green-500 text-white">Open</span>
        @endif
    </div>
    <div class="flex grow flex-col p-5">
        <h3 class="text-lg leading-snug font-extrabold text-ink group-hover:text-brand">{{ $group->name }}</h3>
        <div class="mt-3 space-y-1.5 text-sm text-muted">
            @if ($group->leader)<p class="flex items-center gap-2"><x-icon name="user" class="size-4" /> {{ $group->leader->displayName() }}</p>@endif
            @if ($group->scheduleLabel())<p class="flex items-center gap-2"><x-icon name="clock" class="size-4" /> {{ $group->scheduleLabel() }}</p>@endif
            @if ($group->area)<p class="flex items-center gap-2"><x-icon name="map-pin" class="size-4" /> {{ $group->area }}</p>@endif
        </div>
        <span class="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-bold text-brand">Join this LifeGroup <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" /></span>
    </div>
</a>
