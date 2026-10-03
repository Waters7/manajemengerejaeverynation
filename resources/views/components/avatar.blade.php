@props(['profile' => null, 'name' => null, 'size' => 'size-10'])
@php
    $label = $profile?->full_name ?? $name ?? '?';
    $initials = $profile?->initials()
        ?? collect(explode(' ', $label))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $url = $profile?->photoUrl();
    $palette = ['bg-brand-100 text-brand-700', 'bg-emerald-100 text-emerald-700', 'bg-violet-100 text-violet-700', 'bg-amber-100 text-amber-700', 'bg-rose-100 text-rose-700', 'bg-cyan-100 text-cyan-700'];
    $tone = $palette[crc32($label) % count($palette)];
@endphp
@if ($url)
    <img src="{{ $url }}" alt="{{ $label }}" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-2 ring-white']) }}>
@else
    <span {{ $attributes->class([$size, $tone, 'inline-flex shrink-0 items-center justify-center rounded-full text-xs font-extrabold ring-2 ring-white']) }}>{{ $initials }}</span>
@endif
