<?php

namespace App\Models;

use App\Enums\GalleryCategory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Gallery extends Model
{
    use Auditable, HasSlug, Publishable;

    protected $fillable = ['title', 'slug', 'category', 'event_id', 'gallery_date', 'description', 'cover_path', 'status', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['category' => GalleryCategory::class, 'gallery_date' => 'date'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sequence')->orderBy('id');
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }
        $first = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $first?->thumbUrl();
    }
}
