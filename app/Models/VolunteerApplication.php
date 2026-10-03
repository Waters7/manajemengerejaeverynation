<?php

namespace App\Models;

use App\Enums\VolunteerApplicationStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VolunteerApplication extends Model
{
    use Auditable;

    protected $fillable = [
        'profile_id', 'involvement_request_id', 'name', 'whatsapp', 'email', 'area', 'church_connection', 'skills',
        'experience', 'availability', 'motivation', 'status', 'reviewed_by', 'interview_at', 'decided_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => VolunteerApplicationStatus::class,
            'skills' => 'array',
            'availability' => 'array',
            'interview_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function involvementRequest(): BelongsTo
    {
        return $this->belongsTo(InvolvementRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function ministries(): BelongsToMany
    {
        return $this->belongsToMany(Ministry::class, 'volunteer_application_ministry');
    }

    public function contactNotes(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'notable')->latest();
    }

    public function scopeAwaiting(Builder $query): Builder
    {
        return $query->whereNotIn('status', [VolunteerApplicationStatus::Accepted->value, VolunteerApplicationStatus::Declined->value]);
    }
}
