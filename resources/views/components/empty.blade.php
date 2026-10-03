@props(['title' => 'Nothing here yet', 'icon' => 'inbox'])
<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand"><x-icon :name="$icon" class="size-6" /></span>
    <p class="mt-4 font-bold text-ink">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1 max-w-sm text-sm text-muted">{{ $slot }}</div>
    @endif
</div>
