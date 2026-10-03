<x-layouts.member title="Dashboard">
    <div class="rounded-[2rem] bg-brand p-8 text-white sm:p-10">
        <p class="text-xs font-bold tracking-[0.2em] text-white/70 uppercase">Welcome back,</p>
        <h1 class="mt-1 text-4xl font-extrabold tracking-tight uppercase sm:text-5xl">{{ $user->displayName() }}</h1>
        <p class="mt-3 max-w-xl text-white/80">“Karena itu pergilah, jadikanlah semua bangsa murid-Ku.” — Matius 28:19</p>
    </div>

    @foreach ($announcements as $announcement)
        <div class="card mt-4 flex gap-3 border-brand/30 bg-brand-50/50 p-4">
            <x-icon name="megaphone" class="size-5 shrink-0 text-brand" />
            <div><p class="font-bold">{{ $announcement->title }}</p><p class="text-sm whitespace-pre-line text-slate-600">{{ $announcement->body }}</p></div>
        </div>
    @endforeach

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        {{-- MY JOURNEY --}}
        <a href="{{ route('member.journey') }}" class="card card-hover card-pad lg:col-span-2">
            <div class="flex items-center justify-between">
                <p class="eyebrow">My Journey</p>
                <x-icon name="arrow-right" class="size-4 text-muted" />
            </div>
            @if ($profile->currentStage)
                <p class="mt-3 text-3xl font-extrabold tracking-tight">{{ $profile->currentStage->name }}</p>
                <p class="mt-1 font-semibold text-slate-600">{{ $profile->currentProgram?->name }}</p>
                @if ($currentProgress && $currentProgress->program->chapters->count())
                    <x-progress :value="$currentProgress->completedUnits()" :total="$currentProgress->program->chapters->count()" class="mt-4 max-w-md" />
                @endif
                <div class="mt-5 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted">
                    @if ($discipler)<span>Discipler: <strong class="text-ink">{{ $discipler->displayName() }}</strong></span>@endif
                    @if ($currentProgress?->next_follow_up_at)<span>Next meet-up: <strong class="text-ink">{{ $currentProgress->next_follow_up_at->translatedFormat('j M Y') }}</strong></span>@endif
                </div>
            @else
                <p class="mt-3 text-xl font-extrabold">Start your journey</p>
                <p class="mt-1 text-sm text-muted">Langkah pertama: One 2 One bersama seorang discipler. Isi form Get Involved dan pilih “Memulai One 2 One”.</p>
            @endif
            <div class="mt-6 flex gap-1.5">
                @foreach ($stages as $row)
                    @php $done = $row['programs']->where('status', \App\Enums\ProgressStatus::Completed)->count(); $total = max(1, $row['programs']->count()); @endphp
                    <div class="grow">
                        <div class="progress h-1.5"><span style="width: {{ round($done / $total * 100) }}%; background: {{ $row['stage']->color }}"></span></div>
                        <p class="mt-1.5 text-[0.65rem] font-bold tracking-wider text-muted uppercase">{{ $row['stage']->name }}</p>
                    </div>
                @endforeach
            </div>
        </a>

        {{-- MY LIFEGROUP --}}
        <a href="{{ route('member.lifegroup') }}" class="card card-hover card-pad">
            <div class="flex items-center justify-between"><p class="eyebrow">My LifeGroup</p><x-icon name="users" class="size-5 text-brand" /></div>
            @if ($group = $profile->activeLifeGroups->first())
                <p class="mt-3 text-xl font-extrabold">{{ $group->name }}</p>
                <p class="mt-1 text-sm text-muted">{{ $group->scheduleLabel() }}</p>
                <p class="mt-1 text-sm text-muted">Leader: {{ $group->leader?->displayName() }}</p>
            @else
                <p class="mt-3 text-xl font-extrabold">Find your people</p>
                <p class="mt-1 text-sm text-muted">Belum bergabung di LifeGroup.</p>
                <span class="btn btn-primary btn-sm mt-4">Find a LifeGroup</span>
            @endif
        </a>

        {{-- MY CLASSES --}}
        <a href="{{ route('member.classes') }}" class="card card-hover card-pad">
            <div class="flex items-center justify-between"><p class="eyebrow">My Classes</p><x-icon name="academic" class="size-5 text-brand" /></div>
            @forelse ($classes as $participation)
                <p class="mt-3 font-extrabold">{{ $participation->batch->program->name }}</p>
                <p class="text-sm text-muted">{{ $participation->batch->name }} · <x-badge :value="$participation->status" /></p>
            @empty
                <p class="mt-3 text-sm text-muted">Belum ada kelas aktif. Lihat kelas yang sedang dibuka.</p>
            @endforelse
        </a>

        {{-- MY EVENTS --}}
        <a href="{{ route('member.events') }}" class="card card-hover card-pad">
            <div class="flex items-center justify-between"><p class="eyebrow">My Events</p><x-icon name="calendar" class="size-5 text-brand" /></div>
            @forelse ($registrations->take(2) as $registration)
                <p class="mt-3 font-extrabold">{{ $registration->event->title }}</p>
                <p class="text-sm text-muted">{{ $registration->event->starts_at->translatedFormat('j M · H:i') }}</p>
            @empty
                <p class="mt-3 text-sm text-muted">Belum terdaftar di event mendatang.</p>
            @endforelse
        </a>

        {{-- SERVING --}}
        <a href="{{ route('member.serving') }}" class="card card-hover card-pad">
            <div class="flex items-center justify-between"><p class="eyebrow">Serving</p><x-icon name="hand" class="size-5 text-brand" /></div>
            @forelse ($serving as $membership)
                <p class="mt-3 font-extrabold">{{ $membership->ministry->name }}</p>
                <x-badge :value="$membership->status" />
            @empty
                <p class="mt-3 text-sm text-muted">Pakai talentamu untuk melayani bersama kami.</p>
            @endforelse
        </a>

        {{-- GET INVOLVED --}}
        <div class="card card-pad bg-ink text-white lg:col-span-3">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold tracking-[0.2em] text-brand-300 uppercase">Get involved</p>
                    <p class="mt-1 text-xl font-extrabold">What's your next step?</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('get-involved') }}" class="btn btn-primary btn-sm">Get Involved</a>
                    <a href="{{ route('get-involved.serve') }}" class="btn btn-outline-white btn-sm">Apply to serve</a>
                    <a href="{{ route('prayer.create') }}" class="btn btn-outline-white btn-sm">Prayer request</a>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mt-12 text-lg font-extrabold tracking-tight uppercase">Latest</h2>
    <div class="mt-4 grid gap-5 md:grid-cols-3">
        @if ($devotional)
            <a href="{{ route('devotionals.show', $devotional->slug) }}" class="card card-hover card-pad">
                <p class="eyebrow">Devotional</p>
                <p class="mt-2 font-extrabold">{{ $devotional->title }}</p>
                <p class="mt-1 text-sm text-muted">{{ $devotional->bible_reference }}</p>
            </a>
        @endif
        @if ($sermon)
            <a href="{{ route('sermons.show', $sermon->slug) }}" class="card card-hover card-pad">
                <p class="eyebrow">Sermon</p>
                <p class="mt-2 font-extrabold">{{ $sermon->title }}</p>
                <p class="mt-1 text-sm text-muted">{{ $sermon->speaker }}</p>
            </a>
        @endif
        @foreach ($events->take($devotional && $sermon ? 1 : 3) as $event)
            <a href="{{ route('events.show', $event->slug) }}" class="card card-hover card-pad">
                <p class="eyebrow">Event</p>
                <p class="mt-2 font-extrabold">{{ $event->title }}</p>
                <p class="mt-1 text-sm text-muted">{{ $event->starts_at->translatedFormat('l, j M · H:i') }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.member>
