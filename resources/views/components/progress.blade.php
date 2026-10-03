@props(['value' => 0, 'total' => null, 'label' => true])
@php
    $percent = $total ? (int) round(min($value, $total) / max(1, $total) * 100) : (int) $value;
@endphp
<div {{ $attributes->class(['flex items-center gap-3']) }}>
    <div class="progress"><span style="width: {{ $percent }}%"></span></div>
    @if ($label)
        <span class="shrink-0 text-xs font-bold text-slate-600 tabular-nums">{{ $total ? "{$value} / {$total}" : "{$percent}%" }}</span>
    @endif
</div>
