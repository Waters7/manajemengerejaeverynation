<?php

namespace App\Http\Controllers\Site;

use App\Enums\GalleryCategory;
use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $galleries = Gallery::published()
            ->with(['images' => fn ($q) => $q->limit(1)])
            ->withCount('images')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest('gallery_date')
            ->paginate(12)
            ->withQueryString();

        return view('site.gallery.index', ['galleries' => $galleries, 'categories' => GalleryCategory::options()]);
    }

    public function show(Gallery $gallery): View
    {
        abort_unless($gallery->isLive(), 404);

        return view('site.gallery.show', ['gallery' => $gallery->load('images')]);
    }
}
