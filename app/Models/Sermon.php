<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Sermon extends Model
{
    use Auditable, HasSlug, Publishable, SoftDeletes;

    protected $fillable = [
        'sermon_series_id', 'title', 'slug', 'speaker', 'preached_on', 'bible_text', 'summary', 'reflection',
        'application', 'youtube_url', 'spotify_url', 'cover_path', 'status', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['preached_on' => 'date'];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(SermonSeries::class, 'sermon_series_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(SermonPoint::class)->orderBy('sequence');
    }

    public function youtubeId(): ?string
    {
        if (! $this->youtube_url) {
            return null;
        }
        preg_match('~(?:youtu\.be/|v=|embed/|shorts/|live/)([A-Za-z0-9_-]{11})~', $this->youtube_url, $m);

        return $m[1] ?? null;
    }

    public function spotifyEmbedUrl(): ?string
    {
        if (! $this->spotify_url || ! preg_match('~open\.spotify\.com/(episode|show|track)/([A-Za-z0-9]+)~', $this->spotify_url, $m)) {
            return null;
        }

        return "https://open.spotify.com/embed/{$m[1]}/{$m[2]}";
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }

        return $this->youtubeId() ? 'https://i.ytimg.com/vi/'.$this->youtubeId().'/hqdefault.jpg' : null;
    }
}
