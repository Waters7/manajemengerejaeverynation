<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServingSchedule extends Model
{
    public const STATUSES = ['scheduled' => 'Scheduled', 'confirmed' => 'Confirmed', 'declined' => 'Unable to serve', 'served' => 'Served'];

    protected $fillable = ['ministry_id', 'ministry_role_id', 'profile_id', 'serve_date', 'service_label', 'status', 'notes'];

    protected function casts(): array
    {
        return ['serve_date' => 'date'];
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(MinistryRole::class, 'ministry_role_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
