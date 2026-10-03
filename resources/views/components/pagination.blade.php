@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 text-sm" aria-label="Pagination">
        <p class="text-muted">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-outline btn-sm opacity-40"><x-icon name="chevron-left" class="size-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-outline btn-sm" rel="prev"><x-icon name="chevron-left" class="size-4" /></a>
            @endif
            <span class="px-2 font-semibold tabular-nums">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-outline btn-sm" rel="next"><x-icon name="chevron-right" class="size-4" /></a>
            @else
                <span class="btn btn-outline btn-sm opacity-40"><x-icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
