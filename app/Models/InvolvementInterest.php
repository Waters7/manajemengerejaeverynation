<?php

namespace App\Models;

use App\Enums\InterestAction;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * "Saya tertarik untuk…" options. Labels are editable; `action` drives behaviour.
 */
class InvolvementInterest extends Model
{
    use Auditable, HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'action', 'on_connect_card', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'action' => InterestAction::class,
            'on_connect_card' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function requests(): BelongsToMany
    {
        return $this->belongsToMany(InvolvementRequest::class, 'involvement_request_interest');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /** Selecting this interest reveals the ministry picker. */
    public function revealsMinistries(): bool
    {
        return in_array($this->action, [InterestAction::Volunteer, InterestAction::Ministry], true);
    }
}
