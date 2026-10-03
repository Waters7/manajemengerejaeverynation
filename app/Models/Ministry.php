<?php

namespace App\Models;

use App\Enums\VolunteerStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ministry extends Model
{
    use Auditable, HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'coordinator_id', 'color', 'is_active', 'accepting_volunteers', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'accepting_volunteers' => 'boolean'];
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(MinistryRole::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(MinistryMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', VolunteerStatus::Active->value);
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(VolunteerApplication::class, 'volunteer_application_ministry');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ServingSchedule::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
