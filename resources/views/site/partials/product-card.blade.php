@php
    [$min, $max] = $product->priceRange();
    $soldOut = $product->isSoldOut();
@endphp
<a href="{{ route('store.show', $product->slug) }}" class="group card card-hover flex flex-col overflow-hidden">
    <div @class(['relative overflow-hidden bg-brand-50', 'aspect-[3/4]' => $product->type === \App\Enums\ProductType::Book, 'aspect-square' => $product->type !== \App\Enums\ProductType::Book])>
        @if ($product->coverUrl())
            <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" @class(['size-full object-cover transition duration-500 group-hover:scale-105', 'opacity-60' => $soldOut]) loading="lazy">
        @else
            <div class="flex size-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-brand to-brand-800 p-6 text-center text-white">
                <img src="{{ asset('images/mark-white.png') }}" alt="" class="h-12 w-auto opacity-40">
                <p class="text-sm leading-tight font-extrabold tracking-wide uppercase">{{ $product->name }}</p>
            </div>
        @endif
        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
            @if ($soldOut)
                <span class="badge badge-gray !bg-ink !text-white">Habis</span>
            @elseif ($product->isOnSale())
                <span class="badge badge-red">Promo</span>
            @endif
            @if ($product->is_featured && ! $soldOut)
                <span class="badge badge-amber">Pilihan</span>
            @endif
        </div>
    </div>
    <div class="flex grow flex-col p-4 sm:p-5">
        <p class="text-[0.7rem] font-bold tracking-wider text-brand uppercase">{{ $product->category?->name ?? $product->type->label() }}</p>
        <h3 class="mt-1 leading-snug font-extrabold text-ink group-hover:text-brand">{{ $product->name }}</h3>
        @if ($product->author)<p class="mt-0.5 text-xs text-muted">{{ $product->author }}</p>@endif
        <div class="mt-auto flex items-baseline gap-2 pt-3">
            <span class="text-lg font-extrabold text-ink tabular-nums">@rupiah($min)@if ($max > $min)<span class="text-sm font-bold text-muted"> – @rupiah($max)</span>@endif</span>
            @if ($product->isOnSale())
                <span class="text-xs text-muted line-through tabular-nums">@rupiah($product->compare_at_price)</span>
            @endif
        </div>
    </div>
</a>
