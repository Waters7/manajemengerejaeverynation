@props(['name', 'label' => 'Image', 'current' => null, 'hint' => 'JPG, PNG atau WebP, maks. 5 MB. Otomatis dikonversi ke WebP.'])
<div {{ $attributes->only('class') }} x-data="{ preview: @js($current) }">
    <span class="label">{{ $label }}</span>
    <label class="group relative flex aspect-[16/9] cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-line bg-slate-50 transition hover:border-brand/50">
        <template x-if="preview">
            <img :src="preview" alt="" class="absolute inset-0 size-full object-cover">
        </template>
        <span class="relative z-10 flex flex-col items-center gap-1 rounded-xl bg-white/90 px-4 py-3 text-xs font-semibold text-slate-600 shadow-sm">
            <x-icon name="upload" class="size-5 text-brand" />
            <span x-text="preview ? 'Ganti gambar' : 'Upload gambar'"></span>
        </span>
        <input type="file" name="{{ $name }}" accept="image/*" class="sr-only"
            x-on:change="const file = $event.target.files[0]; if (file) { preview = URL.createObjectURL(file) }">
    </label>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
