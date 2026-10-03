@props(['label', 'value', 'icon' => 'chart', 'href' => null, 'hint' => null, 'tone' => 'brand'])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand',
        'green' => 'bg-green-50 text-green-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['card card-pad flex items-start gap-4', 'card-hover' => $href]) }}>
    <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $tones[$tone] ?? $tones['brand'] }}">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <span class="min-w-0">
        <span class="block text-xs font-bold tracking-wide text-muted uppercase">{{ $label }}</span>
        <span class="mt-1 block text-2xl font-extrabold tracking-tight text-ink tabular-nums">{{ $value }}</span>
        @if ($hint)<span class="mt-0.5 block text-xs text-muted">{{ $hint }}</span>@endif
    </span>
</{{ $tag }}>
