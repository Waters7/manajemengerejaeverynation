<?php

namespace App\Models;

use App\Enums\FollowUpCategory;
use App\Enums\FollowUpTaskStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FollowUpTask extends Model
{
    use Auditable;

    protected $fillable = ['profile_id', 'subject_type', 'subject_id', 'title', 'category', 'assigned_to', 'assigned_by', 'due_date', 'status', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return [
            'category' => FollowUpCategory::class,
            'status' => FollowUpTaskStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [FollowUpTaskStatus::Open->value, FollowUpTaskStatus::InProgress->value]);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->lt(today())
            && in_array($this->status, [FollowUpTaskStatus::Open, FollowUpTaskStatus::InProgress], true);
    }
}
