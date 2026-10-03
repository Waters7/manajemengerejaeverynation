@props(['name', 'label', 'checked' => false, 'value' => '1', 'hint' => null])
<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="inline-flex items-start gap-2.5 text-sm font-medium text-ink">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="checkbox mt-0.5" @checked(old($name, $checked))>
        <span>
            {{ $label }}
            @if ($hint)<span class="block text-xs font-normal text-muted">{{ $hint }}</span>@endif
        </span>
    </label>
</div>
