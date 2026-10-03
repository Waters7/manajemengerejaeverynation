@props(['action' => null])
<form method="GET" action="{{ $action ?? url()->current() }}" {{ $attributes->class(['card mb-4 flex flex-wrap items-end gap-3 p-3 sm:p-4']) }}>
    {{ $slot }}
    <div class="flex gap-2">
        <button class="btn btn-dark btn-sm"><x-icon name="filter" class="size-4" /> Filter</button>
        @if (request()->query())
            <a href="{{ $action ?? url()->current() }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
    </div>
</form>
