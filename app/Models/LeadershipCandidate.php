<?php

namespace App\Models;

use App\Enums\LeadershipStage;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leadership pipeline entry. Pastors/Admins make the final decision —
 * the system never appoints a leader automatically.
 */
class LeadershipCandidate extends Model
{
    use Auditable;

    protected $fillable = ['profile_id', 'stage', 'recommended_by', 'recommendation', 'notes', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['stage' => LeadershipStage::class, 'decided_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
