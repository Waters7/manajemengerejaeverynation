@props(['name', 'title', 'maxWidth' => 'max-w-lg'])
<div x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:keydown.escape.window="open = false">
    @isset($trigger)
        <span x-on:click="open = true">{{ $trigger }}</span>
    @endisset
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/50 backdrop-blur-sm" x-on:click="open = false"></div>
            <div x-show="open" x-transition class="card relative max-h-[90vh] w-full overflow-y-auto {{ $maxWidth }}">
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <h3 class="font-extrabold text-ink">{{ $title }}</h3>
                    <button type="button" x-on:click="open = false" class="rounded-full p-1 text-slate-500 hover:bg-slate-100"><x-icon name="x" class="size-5" /></button>
                </div>
                <div class="px-6 py-5">{{ $slot }}</div>
            </div>
        </div>
    </template>
</div>
