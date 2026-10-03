<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeGroupAttendance extends Model
{
    protected $fillable = ['life_group_meeting_id', 'profile_id', 'status'];

    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(LifeGroupMeeting::class, 'life_group_meeting_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
