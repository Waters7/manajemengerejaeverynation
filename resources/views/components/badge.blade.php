@props(['value' => null, 'color' => null])
@php
    $text = $value instanceof \BackedEnum ? $value->label() : $value;
    $tone = $color ?? ($value instanceof \BackedEnum && method_exists($value, 'color') ? $value->color() : 'gray');
@endphp
@if (filled($text) || $slot->isNotEmpty())
    <span {{ $attributes->class(['badge', 'badge-'.$tone]) }}>{{ $slot->isNotEmpty() ? $slot : $text }}</span>
@endif
