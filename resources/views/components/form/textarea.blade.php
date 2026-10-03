@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'required' => false, 'rows' => 4])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
        {{ $attributes->except(['class', 'id'])->class(['input', 'input-error' => $errors->has($key)]) }}>{{ old($key, $value) }}</textarea>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($key)<p class="error">{{ $message }}</p>@enderror
</div>
