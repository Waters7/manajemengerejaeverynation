@props(['title', 'description' => null, 'eyebrow' => null, 'back' => null])
<div {{ $attributes->class(['mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-muted hover:text-brand">
                <x-icon name="arrow-left" class="size-3.5" /> Back
            </a>
        @endif
        @if ($eyebrow)<p class="eyebrow mb-1">{{ $eyebrow }}</p>@endif
        <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-[1.75rem]">{{ $title }}</h1>
        @if ($description)<p class="mt-1 max-w-2xl text-sm text-muted">{{ $description }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
