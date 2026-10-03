<x-layouts.admin title="Media">
    <x-page-header title="Media library" description="Upload images once and reuse their URL in pages and devotionals. Images are optimised to WebP." />

    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        @csrf
        <div><label class="label">Files</label><input type="file" name="files[]" accept="image/*" multiple required class="input"></div>
        <x-form.input name="folder" label="Folder" placeholder="library" />
        <x-form.input name="alt" label="Alt text" />
        <button class="btn btn-primary btn-sm"><x-icon name="upload" class="size-4" /> Upload</button>
    </form>

    @if ($folders->count() > 1)
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.media.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => ! request('folder')])>All</a>
            @foreach ($folders as $folder)
                <a href="{{ route('admin.media.index', ['folder' => $folder]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('folder') === $folder])>{{ $folder }}</a>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @forelse ($media as $item)
            <div class="card overflow-hidden" x-data="{ copied: false }">
                <img src="{{ $item->thumbUrl() }}" alt="{{ $item->alt }}" class="aspect-square w-full object-cover" loading="lazy">
                <div class="space-y-2 p-3">
                    <p class="truncate text-xs font-semibold" title="{{ $item->filename }}">{{ $item->filename }}</p>
                    <p class="text-[0.65rem] text-muted">{{ $item->width }}×{{ $item->height }} · {{ number_format($item->size / 1024) }} KB</p>
                    <div class="flex gap-1">
                        <button type="button" class="btn btn-outline btn-sm grow" x-on:click="navigator.clipboard.writeText(@js($item->url())); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Copied!' : 'Copy URL'"></button>
                        <form method="POST" action="{{ route('admin.media.destroy', $item) }}" data-confirm="Delete this file? Pages using it will show a broken image.">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card col-span-full"><x-empty title="No media yet" icon="photo" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $media->links() }}</div>
</x-layouts.admin>
