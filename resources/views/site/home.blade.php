<x-layouts.site :transparent="true">
    @php
        $heroImage = $settings->get('hero_image') ? Storage::disk('public')->url($settings->get('hero_image')) : null;
        $aboutImage = $settings->get('about_image') ? Storage::disk('public')->url($settings->get('about_image')) : null;
        $campusImage = $settings->get('campus_image') ? Storage::disk('public')->url($settings->get('campus_image')) : null;
    @endphp

    {{-- 1. HERO --}}
    <section class="duotone flex min-h-[92vh] items-end pt-32 pb-16 text-white sm:items-center sm:pb-24">
        @if ($heroImage)
            <img src="{{ $heroImage }}" alt="Every Nation Bekasi community" fetchpriority="high">
        @else
            <div class="absolute inset-0 -z-10 grid grid-cols-4 gap-1 opacity-40 sm:grid-cols-6" aria-hidden="true">
                @foreach (range(1, 24) as $i)
                    <div class="aspect-square rounded-sm {{ ['bg-brand-300', 'bg-brand-600', 'bg-brand-400', 'bg-brand-700', 'bg-brand-200', 'bg-brand-500'][$i % 6] }}"></div>
                @endforeach
            </div>
        @endif
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="eyebrow !text-white/80">Every Nation Bekasi</p>
            <h1 class="mt-4 text-5xl leading-[0.95] font-extrabold tracking-tight whitespace-pre-line uppercase sm:text-7xl lg:text-8xl">{{ $settings->get('hero_headline') }}</h1>
            <p class="mt-6 max-w-2xl text-xl font-semibold sm:text-2xl">{{ $settings->get('hero_subheadline') }}</p>
            <p class="mt-3 max-w-xl text-base text-white/80">{{ $settings->get('hero_body') }}</p>
            <div class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('lifegroups.index') }}" class="btn btn-white btn-lg">Join a LifeGroup</a>
                <a href="{{ route('get-involved') }}" class="btn btn-primary btn-lg">Get Involved</a>
                <a href="{{ route('events.index') }}" class="btn btn-outline-white btn-lg">Lihat Event <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-14 inline-flex items-center gap-3 rounded-full bg-white/10 px-5 py-2.5 text-sm backdrop-blur">
                <x-icon name="clock" class="size-4" /> <span class="font-bold">Sunday Service</span> <span class="text-white/80">{{ $settings->get('service_times') }}</span>
            </div>
        </div>
    </section>

    {{-- 2. ABOUT --}}
    <section class="py-20 sm:py-28">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8">
            <div>
                <p class="eyebrow">About us</p>
                <h2 class="heading mt-3">{{ $settings->get('about_heading') }}</h2>
                <p class="prose-church mt-6">{{ $settings->get('about_body') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('about') }}" class="btn btn-dark">Get to know us</a>
                    <a href="{{ route('connect') }}" class="btn btn-outline">Plan a visit</a>
                </div>
            </div>
            <div class="relative">
                @if ($aboutImage)
                    <img src="{{ $aboutImage }}" alt="" class="aspect-[4/5] w-full rounded-[2rem] object-cover shadow-[var(--shadow-lift)]" loading="lazy">
                @else
                    <div class="grid aspect-[4/5] place-items-center rounded-[2rem] bg-soft">
                        <img src="{{ asset('images/mark-blue.png') }}" alt="" class="w-1/2 opacity-90">
                    </div>
                @endif
                <div class="absolute -bottom-6 -left-4 hidden rounded-3xl bg-brand p-6 text-white shadow-xl sm:block">
                    <p class="text-3xl font-extrabold">80+</p>
                    <p class="text-sm text-white/80">nations, one family</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. UPCOMING EVENTS --}}
    <section class="bg-canvas py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">What's happening</p>
                    <h2 class="heading mt-3">Upcoming events</h2>
                </div>
                <a href="{{ route('events.index') }}" class="btn btn-outline">All events <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @forelse ($events as $event)
                    @include('site.partials.event-card', ['event' => $event])
                @empty
                    <div class="card md:col-span-3"><x-empty title="Event baru segera hadir" icon="calendar">Ikuti Instagram kami untuk update terbaru.</x-empty></div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- 4. DISCIPLESHIP JOURNEY --}}
    <section class="bg-soft py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="eyebrow">Discipleship journey</p>
                <h2 class="heading mt-3">Grow step by step.</h2>
                <p class="prose-church mt-4">Pemuridan adalah perjalanan bersama. Setiap orang dapat bertumbuh dari Engage, Establish, Equip, hingga Empower — dan kemudian menolong orang lain mengikut Yesus.</p>
            </div>
            <ol class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($stages as $stage)
                    <li class="card card-pad relative overflow-hidden">
                        <span class="absolute -top-4 -right-2 text-[6rem] leading-none font-extrabold text-slate-100">{{ $loop->iteration }}</span>
                        <div class="relative">
                            <span class="inline-block h-1.5 w-10 rounded-full" style="background: {{ $stage->color }}"></span>
                            <h3 class="mt-4 text-2xl font-extrabold tracking-tight">{{ $stage->name }}</h3>
                            <p class="mt-1 text-sm text-muted">{{ $stage->tagline }}</p>
                            <ul class="mt-5 space-y-2">
                                @foreach ($stage->activePrograms as $program)
                                    <li class="flex items-center gap-2 text-sm font-semibold text-slate-700"><x-icon name="check-circle" class="size-4 text-brand" /> {{ $program->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ol>
            <div class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('discipleship') }}" class="btn btn-primary">Explore discipleship</a>
                <a href="{{ route('get-involved') }}" class="btn btn-outline">Start One 2 One</a>
            </div>
        </div>
    </section>

    {{-- 5 & 6. LATEST DEVOTIONAL + SERMON --}}
    <section class="py-20 sm:py-24">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div class="card flex flex-col p-8 sm:p-10">
                <p class="eyebrow">Latest devotional</p>
                @if ($devotional)
                    <h3 class="mt-3 text-2xl font-extrabold tracking-tight">{{ $devotional->title }}</h3>
                    @if ($devotional->verse)
                        <blockquote class="mt-6 border-l-4 border-brand pl-4 text-lg leading-relaxed text-slate-700 italic">{{ $devotional->verse }}</blockquote>
                        <p class="mt-2 pl-5 text-sm font-bold text-brand">{{ $devotional->bible_reference }}</p>
                    @endif
                    <p class="mt-6 line-clamp-3 text-muted">{{ $devotional->opening }}</p>
                    <a href="{{ route('devotionals.show', $devotional->slug) }}" class="mt-auto inline-flex items-center gap-1 pt-6 font-bold text-brand">Read devotional <x-icon name="arrow-right" class="size-4" /></a>
                @else
                    <p class="mt-4 text-muted">Renungan harian akan segera hadir.</p>
                @endif
            </div>
            <div class="overflow-hidden rounded-[var(--radius-card)] bg-ink text-white">
                @if ($sermon)
                    <a href="{{ route('sermons.show', $sermon->slug) }}" class="group relative block aspect-video overflow-hidden bg-brand-900">
                        @if ($sermon->coverUrl())
                            <img src="{{ $sermon->coverUrl() }}" alt="" class="size-full object-cover opacity-80 transition duration-500 group-hover:scale-105" loading="lazy">
                        @endif
                        <span class="absolute inset-0 grid place-items-center">
                            <span class="grid size-16 place-items-center rounded-full bg-white text-brand shadow-xl transition group-hover:scale-110"><x-icon name="play" class="size-7" /></span>
                        </span>
                    </a>
                    <div class="p-8">
                        <p class="text-xs font-bold tracking-[0.2em] text-brand-300 uppercase">Latest sermon{{ $sermon->series ? ' · '.$sermon->series->name : '' }}</p>
                        <h3 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $sermon->title }}</h3>
                        <p class="mt-2 text-sm text-white/70">{{ $sermon->speaker }} · {{ $sermon->preached_on->translatedFormat('j F Y') }} · {{ $sermon->bible_text }}</p>
                    </div>
                @else
                    <div class="p-10"><p class="text-white/70">Khotbah terbaru akan segera hadir.</p></div>
                @endif
            </div>
        </div>
    </section>

    {{-- 7. LIFEGROUP --}}
    <section class="bg-canvas py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-3">
                <div>
                    <p class="eyebrow">LifeGroup</p>
                    <h2 class="heading mt-3">Find your people.</h2>
                    <p class="prose-church mt-4">LifeGroup adalah komunitas kecil tempat kita bertumbuh dalam Firman, saling mendoakan, dan menjalani hidup bersama. Ada LifeGroup untuk mahasiswa, young professionals, couples, dan keluarga di seluruh Bekasi.</p>
                    <a href="{{ route('lifegroups.index') }}" class="btn btn-primary mt-8">Find a LifeGroup</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:col-span-2">
                    @foreach ($lifeGroups->take(2) as $group)
                        @include('site.partials.lifegroup-card', ['group' => $group])
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- 8. CAMPUS MINISTRY --}}
    <section class="duotone py-24 text-white sm:py-32">
        @if ($campusImage)<img src="{{ $campusImage }}" alt="" loading="lazy">@endif
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="eyebrow !text-white/70">Campus Ministry</p>
            <h2 class="display mt-4 whitespace-pre-line">{{ $settings->get('campus_headline') }}</h2>
            <p class="mt-6 max-w-xl text-lg text-white/80">{{ $settings->get('campus_body') }}</p>
            <a href="{{ route('campus') }}" class="btn btn-white btn-lg mt-10">Campus Ministry <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>

    {{-- 9. GET INVOLVED --}}
    <section class="py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Get involved</p>
                <h2 class="heading mt-3">Your next step starts here.</h2>
                <p class="prose-church mt-4">Apa pun langkahmu hari ini, kami ingin berjalan bersamamu.</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Get connected', 'Kenali kami dan biarkan kami mengenalmu.', 'heart', route('get-involved')],
                    ['Join a LifeGroup', 'Temukan komunitas kecil di dekatmu.', 'users', route('lifegroups.index')],
                    ['Start One 2 One', 'Belajar dasar iman bersama seorang discipler.', 'book', route('get-involved')],
                    ['Serve with us', 'Pakai talentamu untuk melayani Tuhan dan sesama.', 'hand', route('get-involved.serve')],
                ] as [$label, $text, $icon, $url])
                    <a href="{{ $url }}" class="group card card-hover card-pad">
                        <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand transition group-hover:bg-brand group-hover:text-white"><x-icon :name="$icon" class="size-6" /></span>
                        <h3 class="mt-5 text-lg font-extrabold">{{ $label }}</h3>
                        <p class="mt-1 text-sm text-muted">{{ $text }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 10. EVENT DOCUMENTATION --}}
    @if ($photos->isNotEmpty())
        <section class="pb-20 sm:pb-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Event documentation</p>
                        <h2 class="heading mt-3">Life together.</h2>
                    </div>
                    <a href="{{ route('gallery.index') }}" class="btn btn-outline">View gallery</a>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach ($photos as $photo)
                        <img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->caption }}" loading="lazy" @class(['size-full rounded-2xl object-cover', 'aspect-square' => ! $loop->first, 'row-span-2 aspect-[1/2] md:aspect-auto' => $loop->first])>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 11. TESTIMONIES --}}
    @if (count($testimonies))
        <section class="bg-canvas py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="eyebrow text-center">Stories</p>
                <h2 class="heading mt-3 text-center">Lives being changed.</h2>
                <div class="mt-12 grid gap-6 md:grid-cols-2">
                    @foreach ($testimonies as $testimony)
                        <figure class="card card-pad">
                            <blockquote class="text-lg leading-relaxed text-slate-700">“{{ $testimony['quote'] }}”</blockquote>
                            <figcaption class="mt-5 flex items-center gap-3">
                                <x-avatar :name="$testimony['name']" size="size-9" />
                                <span class="font-bold">{{ $testimony['name'] }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
