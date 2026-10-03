<x-layouts.admin title="Gallery">
    <x-page-header title="Gallery" description="Event documentation albums.">
        <a href="{{ route('admin.galleries.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New album</a>
    </x-page-header>
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.galleries.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => ! request('category')])>All</a>
        @foreach ($categories as $value => $label)
            <a href="{{ route('admin.galleries.index', ['category' => $value]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('category') === $value])>{{ $label }}</a>
        @endforeach
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($galleries as $gallery)
            <a href="{{ route('admin.galleries.edit', $gallery) }}" class="card card-hover overflow-hidden">
                <div class="aspect-[4/3] bg-slate-100">
                    @if ($gallery->coverUrl())<img src="{{ $gallery->coverUrl() }}" alt="" class="size-full object-cover" loading="lazy">@else<div class="grid size-full place-items-center"><x-icon name="photo" class="size-10 text-slate-300" /></div>@endif
                </div>
                <div class="p-4">
                    <p class="font-extrabold">{{ $gallery->title }}</p>
                    <p class="mt-1 flex items-center justify-between text-xs text-muted"><span>{{ $gallery->category->label() }} · {{ $gallery->images_count }} photos</span><x-badge :value="$gallery->status" /></p>
                </div>
            </a>
        @empty
            <div class="card sm:col-span-2 lg:col-span-3 xl:col-span-4"><x-empty title="No albums yet" icon="photo" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $galleries->links() }}</div>
</x-layouts.admin>
