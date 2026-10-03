<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\GalleryCategory;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTENT → Gallery: event documentation albums with multi-upload (stored as WebP).
 */
class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.galleries.index', [
            'galleries' => Gallery::withCount('images')->with(['images' => fn ($q) => $q->limit(1)])
                ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
                ->latest('gallery_date')->paginate(24)->withQueryString(),
            'categories' => GalleryCategory::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.galleries.form', $this->formData(new Gallery(['gallery_date' => today(), 'status' => ContentStatus::Draft])));
    }

    public function store(Request $request, MediaService $media): RedirectResponse
    {
        $gallery = Gallery::create($this->validated($request) + ['created_by' => $request->user()->id]);
        $this->storeImages($request, $gallery, $media);

        return redirect()->route('admin.galleries.edit', $gallery)->with('status', 'Album created.');
    }

    public function edit(Gallery $gallery): View
    {
        return view('admin.galleries.form', $this->formData($gallery->load('images')));
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $gallery->update($this->validated($request));

        return back()->with('status', 'Album updated.');
    }

    public function destroy(Gallery $gallery, MediaService $media): RedirectResponse
    {
        foreach ($gallery->images as $image) {
            $media->delete($image->path);
        }
        $gallery->delete();

        return redirect()->route('admin.galleries.index')->with('status', 'Album deleted.');
    }

    public function upload(Request $request, Gallery $gallery, MediaService $media): RedirectResponse
    {
        $count = $this->storeImages($request, $gallery, $media);

        return back()->with('status', "{$count} photo(s) uploaded.");
    }

    public function destroyImage(GalleryImage $image, MediaService $media): RedirectResponse
    {
        $media->delete($image->path);
        $image->delete();

        return back()->with('status', 'Photo removed.');
    }

    private function storeImages(Request $request, Gallery $gallery, MediaService $media): int
    {
        $request->validate([
            'images' => ['nullable', 'array', 'max:40'],
            'images.*' => ['image', 'max:10240'],
            'captions' => ['nullable', 'array'],
        ]);

        $sequence = (int) $gallery->images()->max('sequence');
        $count = 0;
        foreach ($request->file('images', []) as $i => $file) {
            $stored = $media->storeImage($file, 'gallery/'.$gallery->id, 2000, 720);
            $gallery->images()->create([
                'path' => $stored['path'],
                'thumb_path' => $stored['thumb_path'],
                'width' => $stored['width'],
                'height' => $stored['height'],
                'caption' => $request->input("captions.{$i}"),
                'sequence' => ++$sequence,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'category' => ['required', Rule::enum(GalleryCategory::class)],
            'event_id' => ['nullable', Rule::exists('events', 'id')],
            'gallery_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Gallery $gallery): array
    {
        return [
            'gallery' => $gallery,
            'categories' => GalleryCategory::options(),
            'statuses' => ContentStatus::options(),
            'events' => Event::latest('starts_at')->limit(100)->pluck('title', 'id'),
        ];
    }
}
