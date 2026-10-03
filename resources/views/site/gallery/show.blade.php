<x-layouts.site :title="$gallery->title">
    @php
        $images = $gallery->images->map(fn ($image) => ['src' => $image->url(), 'thumb' => $image->thumbUrl(), 'caption' => $image->caption])->values();
    @endphp
    <section class="py-12 sm:py-16" x-data="lightbox(@js($images))" x-on:keydown.escape.window="close()" x-on:keydown.arrow-right.window="index !== null && next()" x-on:keydown.arrow-left.window="index !== null && prev()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('gallery.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> All albums</a>
            <p class="eyebrow mt-6">{{ $gallery->category->label() }}@if ($gallery->gallery_date) · {{ $gallery->gallery_date->translatedFormat('j F Y') }}@endif</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight">{{ $gallery->title }}</h1>
            @if ($gallery->description)<p class="mt-3 max-w-2xl text-muted">{{ $gallery->description }}</p>@endif

            <div class="mt-10 columns-2 gap-3 md:columns-3 lg:columns-4 [&>button]:mb-3">
                @foreach ($gallery->images as $image)
                    <button type="button" x-on:click="open({{ $loop->index }})" class="block w-full overflow-hidden rounded-2xl focus:ring-4 focus:ring-brand/30 focus:outline-none">
                        <img src="{{ $image->thumbUrl() }}" alt="{{ $image->caption }}" class="w-full transition duration-300 hover:scale-[1.03]" loading="lazy" @if ($image->width) width="{{ $image->width }}" height="{{ $image->height }}" @endif>
                    </button>
                @endforeach
            </div>
            @if ($gallery->images->isEmpty())
                <div class="card mt-10"><x-empty title="Foto akan segera diunggah" icon="photo" /></div>
            @endif
        </div>

        <div x-show="current" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/95 p-4" x-on:click.self="close()">
            <button type="button" x-on:click="close()" class="absolute top-4 right-4 rounded-full p-2 text-white/80 hover:bg-white/10" aria-label="Close"><x-icon name="x" class="size-7" /></button>
            <button type="button" x-on:click="prev()" class="absolute left-2 rounded-full p-3 text-white/80 hover:bg-white/10 sm:left-6" aria-label="Previous"><x-icon name="chevron-left" class="size-8" /></button>
            <figure class="max-h-full max-w-5xl text-center">
                <img :src="current?.src" :alt="current?.caption ?? ''" class="max-h-[85vh] w-auto rounded-xl object-contain">
                <figcaption class="mt-3 text-sm text-white/80" x-text="current?.caption ?? ''"></figcaption>
            </figure>
            <button type="button" x-on:click="next()" class="absolute right-2 rounded-full p-3 text-white/80 hover:bg-white/10 sm:right-6" aria-label="Next"><x-icon name="chevron-right" class="size-8" /></button>
        </div>
    </section>
</x-layouts.site>
