<x-layouts.site title="Gallery" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Gallery', 'title' => "LIFE\nTOGETHER.", 'lead' => 'Dokumentasi ibadah, LifeGroup, campus, dan momen kebersamaan Every Nation Bekasi.'])

    <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-wrap gap-2">
                <a href="{{ route('gallery.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => ! request('category')])>All</a>
                @foreach ($categories as $value => $label)
                    <a href="{{ route('gallery.index', ['category' => $value]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('category') === $value])>{{ $label }}</a>
                @endforeach
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($galleries as $gallery)
                    <a href="{{ route('gallery.show', $gallery->slug) }}" class="group relative block aspect-[4/3] overflow-hidden rounded-[var(--radius-card)] bg-brand-900">
                        @if ($gallery->coverUrl())
                            <img src="{{ $gallery->coverUrl() }}" alt="" class="size-full object-cover opacity-90 transition duration-500 group-hover:scale-105" loading="lazy">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-ink/85 via-ink/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                            <p class="text-xs font-bold tracking-wider text-white/70 uppercase">{{ $gallery->category->label() }} · {{ $gallery->images_count }} photos</p>
                            <h2 class="mt-1 text-xl font-extrabold">{{ $gallery->title }}</h2>
                            @if ($gallery->gallery_date)<p class="text-sm text-white/70">{{ $gallery->gallery_date->translatedFormat('j F Y') }}</p>@endif
                        </div>
                    </a>
                @empty
                    <div class="card sm:col-span-2 lg:col-span-3"><x-empty title="Belum ada album" icon="photo" /></div>
                @endforelse
            </div>
            <div class="mt-10">{{ $galleries->links() }}</div>
        </div>
    </section>
</x-layouts.site>
