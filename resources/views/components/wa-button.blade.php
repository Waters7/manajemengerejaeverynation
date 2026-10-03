@props(['href' => null, 'label' => 'Contact via WhatsApp', 'size' => 'btn-sm'])
@if ($href)
    <a href="{{ $href }}" target="_blank" rel="noopener" {{ $attributes->class(['btn btn-whatsapp', $size]) }}>
        <x-icon name="whatsapp" class="size-4" /> {{ $label }}
    </a>
@endif
