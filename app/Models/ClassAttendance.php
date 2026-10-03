<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAttendance extends Model
{
    protected $table = 'class_attendance';

    protected $fillable = ['class_session_id', 'class_participant_id', 'status'];

    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ClassParticipant::class, 'class_participant_id');
    }
}
