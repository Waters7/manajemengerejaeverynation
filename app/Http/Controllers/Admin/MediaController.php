<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SYSTEM → Media library: reusable images (converted to WebP) with copyable URLs.
 */
class MediaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.media', [
            'media' => Media::with('uploader')
                ->when($request->filled('folder'), fn ($q) => $q->where('folder', $request->string('folder')))
                ->latest()->paginate(36)->withQueryString(),
            'folders' => Media::distinct()->orderBy('folder')->pluck('folder'),
        ]);
    }

    public function store(Request $request, MediaService $service): RedirectResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'max:30'],
            'files.*' => ['image', 'max:10240'],
            'folder' => ['nullable', 'alpha_dash', 'max:50'],
            'alt' => ['nullable', 'string', 'max:190'],
        ]);

        foreach ($request->file('files') as $file) {
            $service->storeInLibrary($file, $data['folder'] ?? 'library', $data['alt'] ?? null);
        }

        return back()->with('status', count($request->file('files')).' file(s) uploaded.');
    }

    public function destroy(Media $media, MediaService $service): RedirectResponse
    {
        $service->delete($media->path);
        $media->delete();

        return back()->with('status', 'File deleted.');
    }
}
