@if (session('status') || session('error') || $errors->any())
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition
        {{ $attributes->class(['fixed right-4 bottom-4 z-[60] w-[calc(100%-2rem)] max-w-sm']) }}>
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl bg-ink px-4 py-3 text-sm text-white shadow-xl" role="status">
                <x-icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-green-400" />
                <p class="grow">{{ session('status') }}</p>
                <button type="button" x-on:click="show = false" class="text-white/60 hover:text-white"><x-icon name="x" class="size-4" /></button>
            </div>
        @else
            <div class="flex items-start gap-3 rounded-2xl bg-danger px-4 py-3 text-sm text-white shadow-xl" role="alert">
                <x-icon name="info" class="mt-0.5 size-5 shrink-0" />
                <p class="grow">{{ session('error') ?? 'Mohon periksa kembali isian yang ditandai.' }}</p>
                <button type="button" x-on:click="show = false" class="text-white/70 hover:text-white"><x-icon name="x" class="size-4" /></button>
            </div>
        @endif
    </div>
@endif
