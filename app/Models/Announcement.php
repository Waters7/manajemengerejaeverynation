<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use Auditable, Publishable;

    protected $fillable = ['title', 'body', 'audience', 'is_pinned', 'status', 'published_at', 'expires_at', 'created_by'];

    protected function casts(): array
    {
        return ['audience' => AnnouncementAudience::class, 'is_pinned' => 'boolean', 'expires_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->published()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at');
    }
}
