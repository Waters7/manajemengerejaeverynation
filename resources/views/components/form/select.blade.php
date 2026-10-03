@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $selected = old($key, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
        {{ $attributes->except(['class', 'id'])->class(['input pr-9', 'input-error' => $errors->has($key)]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($key)<p class="error">{{ $message }}</p>@enderror
</div>
