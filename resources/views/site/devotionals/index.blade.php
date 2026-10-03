<x-layouts.site title="Devotional" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Devotional', 'title' => "DAILY\nBREAD.", 'lead' => 'Renungan untuk menolongmu bertumbuh dalam Firman setiap hari.'])

    <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-10 flex max-w-md gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul atau ayat…" class="input">
                <button class="btn btn-primary">Search</button>
            </form>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($devotionals as $devotional)
                    <a href="{{ route('devotionals.show', $devotional->slug) }}" class="group card card-hover flex flex-col overflow-hidden">
                        @if ($devotional->coverUrl())
                            <img src="{{ $devotional->coverUrl() }}" alt="" class="aspect-[16/9] w-full object-cover" loading="lazy">
                        @endif
                        <div class="flex grow flex-col p-6">
                            <p class="text-xs font-bold tracking-wider text-brand uppercase">{{ $devotional->devotional_date->translatedFormat('l, j F Y') }}</p>
                            <h2 class="mt-2 text-xl font-extrabold group-hover:text-brand">{{ $devotional->title }}</h2>
                            @if ($devotional->bible_reference)<p class="mt-1 text-sm font-semibold text-muted">{{ $devotional->bible_reference }}</p>@endif
                            <p class="mt-3 line-clamp-3 text-sm text-muted">{{ $devotional->opening ?: Str::limit(strip_tags($devotional->body), 160) }}</p>
                        </div>
                    </a>
                @empty
                    <div class="card md:col-span-2 lg:col-span-3"><x-empty title="Belum ada renungan" icon="book" /></div>
                @endforelse
            </div>
            <div class="mt-10">{{ $devotionals->links() }}</div>
        </div>
    </section>
</x-layouts.site>
