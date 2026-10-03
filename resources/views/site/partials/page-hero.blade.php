<section class="duotone pt-40 pb-20 text-white sm:pb-24">
    @isset($image)
        @if ($image)<img src="{{ $image }}" alt="">@endif
    @endisset
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @isset($eyebrow)<p class="eyebrow !text-white/75">{{ $eyebrow }}</p>@endisset
        <h1 class="display mt-3 whitespace-pre-line">{{ $title }}</h1>
        @isset($lead)<p class="mt-5 max-w-2xl text-lg text-white/85">{{ $lead }}</p>@endisset
    </div>
</section>
