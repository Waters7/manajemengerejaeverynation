<x-layouts.site :title="$devotional->title" :description="$devotional->opening">
    <article class="py-12 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <a href="{{ route('devotionals.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> All devotionals</a>
            <p class="eyebrow mt-8">{{ $devotional->devotional_date->translatedFormat('l, j F Y') }}</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $devotional->title }}</h1>
            @if ($devotional->author)<p class="mt-3 text-sm font-semibold text-muted">by {{ $devotional->author }}</p>@endif

            @if ($devotional->coverUrl())
                <img src="{{ $devotional->coverUrl() }}" alt="" class="mt-8 aspect-[16/9] w-full rounded-[2rem] object-cover">
            @endif

            @if ($devotional->verse)
                <figure class="mt-10 rounded-3xl bg-soft p-8">
                    <blockquote class="text-xl leading-relaxed font-semibold text-brand-800">{{ $devotional->verse }}</blockquote>
                    <figcaption class="mt-3 text-sm font-extrabold tracking-wider text-brand uppercase">{{ $devotional->bible_reference }}</figcaption>
                </figure>
            @endif

            @if ($devotional->opening)<p class="mt-10 text-xl leading-relaxed text-slate-700">{{ $devotional->opening }}</p>@endif
            <div class="prose-church mt-6">{!! $devotional->body !!}</div>

            <div class="mt-12 grid gap-4">
                @foreach (['Reflection' => $devotional->reflection, 'Application' => $devotional->application, 'Prayer' => $devotional->prayer] as $heading => $text)
                    @if ($text)
                        <section class="card card-pad">
                            <h2 class="eyebrow">{{ $heading }}</h2>
                            <p class="mt-2 leading-relaxed whitespace-pre-line text-slate-700 {{ $heading === 'Prayer' ? 'italic' : '' }}">{{ $text }}</p>
                        </section>
                    @endif
                @endforeach
            </div>

            <nav class="mt-12 flex justify-between gap-4 border-t border-line pt-6 text-sm font-bold">
                @if ($previous)<a href="{{ route('devotionals.show', $previous->slug) }}" class="text-brand">← {{ Str::limit($previous->title, 30) }}</a>@else<span></span>@endif
                @if ($next)<a href="{{ route('devotionals.show', $next->slug) }}" class="text-right text-brand">{{ Str::limit($next->title, 30) }} →</a>@endif
            </nav>
        </div>
    </article>
</x-layouts.site>
