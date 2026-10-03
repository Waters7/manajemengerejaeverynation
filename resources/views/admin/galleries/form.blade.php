<x-layouts.admin :title="$gallery->exists ? $gallery->title : 'New album'">
    <x-page-header :title="$gallery->exists ? $gallery->title : 'New album'" :back="route('admin.galleries.index')">
        @if ($gallery->exists && $gallery->isLive())<a href="{{ route('gallery.show', $gallery->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> Public page</a>@endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $gallery->exists ? route('admin.galleries.update', $gallery) : route('admin.galleries.store') }}" enctype="multipart/form-data" class="card card-pad h-fit space-y-4">
            @csrf
            @if ($gallery->exists) @method('PUT') @endif
            <x-form.input name="title" label="Title" :value="$gallery->title" required />
            <x-form.select name="category" label="Category" :options="$categories" :value="$gallery->category" required />
            <x-form.select name="event_id" label="Event" :options="$events" :value="$gallery->event_id" placeholder="—" />
            <x-form.input name="gallery_date" type="date" label="Date" :value="$gallery->gallery_date" />
            <x-form.textarea name="description" label="Description" :value="$gallery->description" rows="3" />
            @include('admin.partials.publish-fields', ['model' => $gallery])
            @unless ($gallery->exists)
                <div>
                    <label class="label">Photos</label>
                    <input type="file" name="images[]" accept="image/*" multiple class="input">
                    <p class="hint">Select multiple photos — they are resized and converted to WebP.</p>
                </div>
            @endunless
            <button class="btn btn-primary w-full">{{ $gallery->exists ? 'Save album' : 'Create album' }}</button>
        </form>

        @if ($gallery->exists)
            <div class="card lg:col-span-2">
                <form method="POST" action="{{ route('admin.galleries.images.store', $gallery) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 border-b border-line p-5">
                    @csrf
                    <input type="file" name="images[]" accept="image/*" multiple required class="input max-w-sm">
                    <button class="btn btn-primary btn-sm"><x-icon name="upload" class="size-4" /> Upload photos</button>
                    @error('images.*')<p class="error">{{ $message }}</p>@enderror
                </form>
                <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 xl:grid-cols-4">
                    @forelse ($gallery->images as $image)
                        <div class="group relative overflow-hidden rounded-xl">
                            <img src="{{ $image->thumbUrl() }}" alt="{{ $image->caption }}" class="aspect-square w-full object-cover" loading="lazy">
                            <form method="POST" action="{{ route('admin.galleries.images.destroy', $image) }}" class="absolute top-2 right-2 opacity-0 transition group-hover:opacity-100" data-confirm="Remove this photo?">
                                @csrf @method('DELETE')
                                <button class="grid size-8 place-items-center rounded-full bg-white/90 text-danger shadow"><x-icon name="trash" class="size-4" /></button>
                            </form>
                        </div>
                    @empty
                        <p class="col-span-full text-sm text-muted">No photos yet.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    @if ($gallery->exists)
        <form method="POST" action="{{ route('admin.galleries.destroy', $gallery) }}" data-confirm="Delete this album and all its photos?" class="mt-6">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /> Delete album</button>
        </form>
    @endif
</x-layouts.admin>
