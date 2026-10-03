<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Contact log / internal note about a person. Internal notes are never shown to members.
 */
class FollowUp extends Model
{
    protected $fillable = ['profile_id', 'notable_type', 'notable_id', 'user_id', 'type', 'body', 'is_internal'];

    protected function casts(): array
    {
        return ['type' => ContactType::class, 'is_internal' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
