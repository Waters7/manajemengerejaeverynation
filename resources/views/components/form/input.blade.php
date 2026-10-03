@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $formatted = $value instanceof \DateTimeInterface ? $value->format($type === 'datetime-local' ? 'Y-m-d\TH:i' : ($type === 'time' ? 'H:i' : 'Y-m-d')) : $value;
    $current = $type === 'password' ? null : old($key, $formatted);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}" @required($required)
        {{ $attributes->except(['class', 'id'])->class(['input', 'input-error' => $errors->has($key)]) }}>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($key)<p class="error">{{ $message }}</p>@enderror
</div>
