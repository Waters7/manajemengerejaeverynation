<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscipleshipMeeting extends Model
{
    protected $fillable = ['discipler_relationship_id', 'met_on', 'topic', 'notes', 'next_follow_up_at', 'recorded_by'];

    protected function casts(): array
    {
        return ['met_on' => 'date', 'next_follow_up_at' => 'date'];
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(DisciplerRelationship::class, 'discipler_relationship_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
