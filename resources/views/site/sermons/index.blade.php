<x-layouts.site title="Sermons" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Sermons', 'title' => "HEAR THE\nWORD.", 'lead' => 'Khotbah Sunday Service Every Nation Bekasi — tonton, dengarkan, dan renungkan.'])

    <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="card mb-10 grid gap-3 p-4 sm:grid-cols-3 sm:items-end">
                <x-form.input name="q" type="search" label="Search" :value="request('q')" placeholder="Judul, pembicara, ayat…" />
                <x-form.select name="series" label="Series" :options="$series->pluck('name', 'slug')->all()" :value="request('series')" placeholder="All series" />
                <button class="btn btn-primary">Search</button>
            </form>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($sermons as $sermon)
                    <a href="{{ route('sermons.show', $sermon->slug) }}" class="group card card-hover overflow-hidden">
                        <div class="relative aspect-video bg-brand-900">
                            @if ($sermon->coverUrl())<img src="{{ $sermon->coverUrl() }}" alt="" class="size-full object-cover" loading="lazy">@endif
                            <span class="absolute right-4 bottom-4 grid size-11 place-items-center rounded-full bg-white text-brand shadow-lg transition group-hover:scale-110"><x-icon name="play" class="size-5" /></span>
                        </div>
                        <div class="p-6">
                            @if ($sermon->series)<p class="text-xs font-bold tracking-wider text-brand uppercase">{{ $sermon->series->name }}</p>@endif
                            <h2 class="mt-1 text-xl font-extrabold group-hover:text-brand">{{ $sermon->title }}</h2>
                            <p class="mt-2 text-sm text-muted">{{ $sermon->speaker }} · {{ $sermon->preached_on->translatedFormat('j M Y') }}</p>
                            @if ($sermon->bible_text)<p class="mt-1 text-sm font-semibold text-slate-600">{{ $sermon->bible_text }}</p>@endif
                        </div>
                    </a>
                @empty
                    <div class="card md:col-span-2 lg:col-span-3"><x-empty title="Belum ada khotbah" icon="play" /></div>
                @endforelse
            </div>
            <div class="mt-10">{{ $sermons->links() }}</div>
        </div>
    </section>
</x-layouts.site>
