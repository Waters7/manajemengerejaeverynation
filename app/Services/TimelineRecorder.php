<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\TimelineEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes milestones to a person's timeline (First Visit, Joined LifeGroup, Completed One 2 One…).
 */
class TimelineRecorder
{
    public function record(Profile $profile, string $type, string $title, ?string $description = null, ?Model $subject = null, mixed $occurredAt = null): TimelineEntry
    {
        return $profile->timeline()->create([
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'occurred_at' => $occurredAt ?? now(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'created_by' => Auth::id(),
        ]);
    }
}
