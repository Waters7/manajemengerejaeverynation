<?php

namespace App\Models;

use App\Enums\DiscoverySource;
use App\Enums\NewcomerJourney;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newcomer extends Model
{
    use Auditable;

    protected $fillable = ['profile_id', 'journey_status', 'first_visit_date', 'source', 'assigned_to', 'contacted_at', 'connected_at', 'notes'];

    protected function casts(): array
    {
        return [
            'journey_status' => NewcomerJourney::class,
            'source' => DiscoverySource::class,
            'first_visit_date' => 'date',
            'contacted_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
