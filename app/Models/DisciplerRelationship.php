<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DisciplerRelationship extends Model
{
    use Auditable;

    protected $fillable = ['discipler_profile_id', 'disciple_profile_id', 'status', 'started_at', 'ended_at', 'notes'];

    protected function casts(): array
    {
        return ['started_at' => 'date', 'ended_at' => 'date'];
    }

    public function discipler(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'discipler_profile_id');
    }

    public function disciple(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'disciple_profile_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(DiscipleshipMeeting::class)->orderByDesc('met_on');
    }

    public function latestMeeting(): HasOne
    {
        return $this->hasOne(DiscipleshipMeeting::class)->latestOfMany('met_on');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
