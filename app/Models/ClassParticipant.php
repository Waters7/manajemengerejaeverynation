<?php

namespace App\Models;

use App\Enums\ParticipantStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassParticipant extends Model
{
    use Auditable;

    protected $fillable = ['class_batch_id', 'profile_id', 'status', 'registered_at', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return ['status' => ParticipantStatus::class, 'registered_at' => 'datetime', 'completed_at' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class);
    }
}
