<x-layouts.site :title="$sermon->title" :description="$sermon->summary">
    <article class="py-12 sm:py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <a href="{{ route('sermons.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> All sermons</a>
            @if ($sermon->series)<p class="eyebrow mt-8">{{ $sermon->series->name }}</p>@endif
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $sermon->title }}</h1>
            <p class="mt-3 text-muted">{{ $sermon->speaker }} · {{ $sermon->preached_on->translatedFormat('l, j F Y') }}@if ($sermon->bible_text) · <strong class="text-ink">{{ $sermon->bible_text }}</strong>@endif</p>

            @if ($sermon->youtubeId())
                <div class="mt-8 aspect-video overflow-hidden rounded-[1.5rem] bg-ink shadow-[var(--shadow-lift)]">
                    <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $sermon->youtubeId() }}" title="{{ $sermon->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                </div>
            @elseif ($sermon->coverUrl())
                <img src="{{ $sermon->coverUrl() }}" alt="" class="mt-8 aspect-video w-full rounded-[1.5rem] object-cover">
            @endif
            @if ($sermon->spotifyEmbedUrl())
                <iframe class="mt-4 w-full rounded-2xl" src="{{ $sermon->spotifyEmbedUrl() }}" height="152" allow="encrypted-media" loading="lazy" title="Spotify"></iframe>
            @endif

            @if ($sermon->summary)
                <section class="mt-10"><h2 class="eyebrow">Summary</h2><p class="prose-church mt-3 whitespace-pre-line">{{ $sermon->summary }}</p></section>
            @endif

            @if ($sermon->points->isNotEmpty())
                <section class="mt-10">
                    <h2 class="eyebrow">Key points</h2>
                    <ol class="mt-4 space-y-3">
                        @foreach ($sermon->points as $point)
                            <li class="card flex gap-4 p-5">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand text-sm font-extrabold text-white">{{ $loop->iteration }}</span>
                                <div><p class="font-extrabold">{{ $point->title }}</p>@if ($point->body)<p class="mt-1 text-muted">{{ $point->body }}</p>@endif</div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <div class="mt-10 grid gap-4 sm:grid-cols-2">
                @foreach (['Reflection' => $sermon->reflection, 'Application' => $sermon->application] as $heading => $text)
                    @if ($text)
                        <section class="rounded-3xl bg-soft p-6"><h2 class="eyebrow">{{ $heading }}</h2><p class="mt-2 whitespace-pre-line text-slate-700">{{ $text }}</p></section>
                    @endif
                @endforeach
            </div>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="bg-canvas py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="heading">More sermons</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($more as $item)
                        <a href="{{ route('sermons.show', $item->slug) }}" class="card card-hover card-pad">
                            <p class="text-xs font-bold text-brand uppercase">{{ $item->preached_on->translatedFormat('j M Y') }}</p>
                            <p class="mt-1 text-lg font-extrabold">{{ $item->title }}</p>
                            <p class="text-sm text-muted">{{ $item->speaker }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
