<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeGroupMeeting extends Model
{
    protected $fillable = ['life_group_id', 'meeting_date', 'topic', 'leader_profile_id', 'location', 'notes', 'visitor_count', 'recorded_by'];

    protected function casts(): array
    {
        return ['meeting_date' => 'date'];
    }

    public function lifeGroup(): BelongsTo
    {
        return $this->belongsTo(LifeGroup::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'leader_profile_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LifeGroupAttendance::class);
    }

    public function presentCount(): int
    {
        return $this->attendances->where('status', AttendanceStatus::Present)->count();
    }
}
