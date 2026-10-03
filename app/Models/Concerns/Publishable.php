<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Draft / Scheduled / Published / Archived content.
 * Scheduled content becomes visible once `published_at` has passed.
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts(['status' => ContentStatus::class, 'published_at' => 'datetime']);
    }

    public static function bootPublishable(): void
    {
        static::saving(function ($model) {
            if ($model->status === ContentStatus::Published && ! $model->published_at) {
                $model->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', ContentStatus::Published->value)
                ->orWhere(fn (Builder $q) => $q->where('status', ContentStatus::Scheduled->value)->where('published_at', '<=', now()));
        });
    }

    public function isLive(): bool
    {
        return $this->status === ContentStatus::Published
            || ($this->status === ContentStatus::Scheduled && $this->published_at?->isPast());
    }
}
