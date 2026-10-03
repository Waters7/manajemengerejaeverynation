<?php

namespace App\Models;

use App\Enums\DiscoverySource;
use App\Enums\Gender;
use App\Enums\InterestAction;
use App\Enums\InvolvementStatus;
use App\Enums\InvolvementType;
use App\Enums\LifeStage;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvolvementRequest extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'profile_id', 'type', 'full_name', 'nickname', 'gender', 'birth_date', 'whatsapp', 'email', 'area', 'occupation',
        'company', 'campus_name', 'campus_id', 'life_stage', 'source', 'source_other', 'experience', 'skills', 'availability',
        'motivation', 'status', 'assigned_to', 'assigned_at', 'due_date', 'contacted_at', 'archived_at', 'submitted_ip',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvolvementType::class,
            'status' => InvolvementStatus::class,
            'gender' => Gender::class,
            'life_stage' => LifeStage::class,
            'source' => DiscoverySource::class,
            'birth_date' => 'date',
            'due_date' => 'date',
            'skills' => 'array',
            'availability' => 'array',
            'assigned_at' => 'datetime',
            'contacted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(InvolvementInterest::class, 'involvement_request_interest');
    }

    public function ministries(): BelongsToMany
    {
        return $this->belongsToMany(Ministry::class, 'involvement_request_ministry');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'notable')->latest();
    }

    public function volunteerApplications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class);
    }

    public function hasInterest(InterestAction $action): bool
    {
        return $this->interests->contains(fn ($interest) => $interest->action === $action);
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function displayName(): string
    {
        return $this->nickname ?: $this->full_name;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeWithInterestAction(Builder $query, InterestAction|string $action): Builder
    {
        $value = $action instanceof InterestAction ? $action->value : $action;

        return $query->whereHas('interests', fn ($q) => $q->where('action', $value));
    }
}
