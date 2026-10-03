<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    protected $fillable = ['class_batch_id', 'session_number', 'topic', 'session_date', 'start_time', 'end_time', 'facilitator_profile_id', 'room'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'facilitator_profile_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class);
    }
}
