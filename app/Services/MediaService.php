<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Stores uploaded images as optimised WebP (plus a thumbnail) on the public disk.
 */
class MediaService
{
    private ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(new Driver);
    }

    /**
     * @return array{path: string, thumb_path: string, width: int, height: int, size: int}
     */
    public function storeImage(UploadedFile $file, string $folder, int $maxWidth = 1920, int $thumbWidth = 640): array
    {
        $disk = Storage::disk('public');
        $name = now()->format('Y/m').'/'.Str::ulid();

        $image = $this->images->decodePath($file->getRealPath())->orient()->scaleDown(width: $maxWidth);
        $encoded = $image->encode(new WebpEncoder(quality: 82, strip: true));
        $path = "{$folder}/{$name}.webp";
        $disk->put($path, $encoded->toString());

        $thumb = $this->images->decodePath($file->getRealPath())->orient()->scaleDown(width: $thumbWidth);
        $thumbPath = "{$folder}/{$name}-thumb.webp";
        $disk->put($thumbPath, $thumb->encode(new WebpEncoder(quality: 75, strip: true))->toString());

        return [
            'path' => $path,
            'thumb_path' => $thumbPath,
            'width' => $image->width(),
            'height' => $image->height(),
            'size' => $encoded->size(),
        ];
    }

    /** Store an image and register it in the media library. */
    public function storeInLibrary(UploadedFile $file, string $folder = 'library', ?string $alt = null): Media
    {
        $stored = $this->storeImage($file, $folder);

        return Media::create([
            'disk' => 'public',
            'path' => $stored['path'],
            'thumb_path' => $stored['thumb_path'],
            'filename' => $file->getClientOriginalName(),
            'mime_type' => 'image/webp',
            'size' => $stored['size'],
            'width' => $stored['width'],
            'height' => $stored['height'],
            'alt' => $alt,
            'folder' => $folder,
            'uploaded_by' => Auth::id(),
        ]);
    }

    /** Replace an image column value, deleting the old file. */
    public function replace(?string $oldPath, ?UploadedFile $file, string $folder, int $maxWidth = 1920): ?string
    {
        if (! $file) {
            return $oldPath;
        }
        $this->delete($oldPath);

        return $this->storeImage($file, $folder, $maxWidth)['path'];
    }

    public function delete(?string ...$paths): void
    {
        $paths = array_filter($paths);
        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
            Storage::disk('public')->delete(array_map(fn ($p) => preg_replace('/\.webp$/', '-thumb.webp', $p), $paths));
        }
    }
}
