<?php

namespace App\Models;

use App\Enums\PrayerStatus;
use App\Enums\PrayerVisibility;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrayerRequest extends Model
{
    use Auditable, SoftDeletes;

    /** Prayer content is private; keep it out of the audit trail. */
    protected array $auditExclude = ['request', 'praise_report'];

    protected $fillable = [
        'profile_id', 'user_id', 'life_group_id', 'name', 'contact', 'request', 'visibility', 'status', 'is_anonymous',
        'praise_report', 'answered_at', 'assigned_to', 'source',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'encrypted',
            'praise_report' => 'encrypted',
            'visibility' => PrayerVisibility::class,
            'status' => PrayerStatus::class,
            'is_anonymous' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function lifeGroup(): BelongsTo
    {
        return $this->belongsTo(LifeGroup::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requesterName(): string
    {
        return $this->is_anonymous ? 'Anonymous' : ($this->name ?: $this->profile?->displayName() ?? 'Anonymous');
    }
}
