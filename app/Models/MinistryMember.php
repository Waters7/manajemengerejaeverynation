<?php

namespace App\Models;

use App\Enums\VolunteerStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A volunteer serving in a ministry. Serving never grants a system role.
 */
class MinistryMember extends Model
{
    use Auditable;

    protected $fillable = ['ministry_id', 'profile_id', 'ministry_role_id', 'skills', 'availability', 'joined_at', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'status' => VolunteerStatus::class,
            'skills' => 'array',
            'availability' => 'array',
            'joined_at' => 'date',
        ];
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(MinistryRole::class, 'ministry_role_id');
    }
}
