<?php

namespace App\Models;

use App\Enums\CarePriority;
use App\Enums\CareStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Restricted: only roles with `pastoral.view` may read these records.
 */
class PastoralCareRequest extends Model
{
    use Auditable, SoftDeletes;

    protected array $auditExclude = ['description', 'subject'];

    protected $fillable = [
        'profile_id', 'pastoral_care_category_id', 'requester_name', 'contact', 'subject', 'description', 'status',
        'priority', 'assigned_to', 'opened_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'encrypted',
            'status' => CareStatus::class,
            'priority' => CarePriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PastoralCareCategory::class, 'pastoral_care_category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PastoralCareNote::class)->latest();
    }
}
