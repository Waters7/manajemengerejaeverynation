<x-layouts.admin title="Books">
    <x-page-header title="Books" description="Book-based programs (One 2 One, Purple Book…) and their chapters.">
        <a href="{{ route('admin.curriculum.programs.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New program</a>
    </x-page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($books as $book)
            <a href="{{ route('admin.curriculum.programs.edit', $book) }}" class="card card-hover card-pad">
                <x-icon name="book" class="size-7 text-brand" />
                <p class="eyebrow mt-4">{{ $book->stage?->name }}</p>
                <h2 class="mt-1 text-xl font-extrabold">{{ $book->name }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $book->chapters_count }} chapters</p>
                <div class="mt-4 flex gap-4 text-sm">
                    <span><strong>{{ $book->readers_count }}</strong> reading</span>
                    <span><strong>{{ $book->completed_count }}</strong> completed</span>
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty title="No book programs yet" icon="book" /></div>
        @endforelse
    </div>
</x-layouts.admin>
