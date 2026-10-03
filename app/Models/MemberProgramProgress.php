<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person's progress through one curriculum program (e.g. One 2 One, Purple Book).
 */
class MemberProgramProgress extends Model
{
    use Auditable;

    protected $table = 'member_program_progress';

    protected $fillable = [
        'profile_id', 'discipleship_program_id', 'discipler_profile_id', 'class_batch_id', 'status', 'started_at',
        'expected_completion_at', 'completed_at', 'last_activity_at', 'next_follow_up_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'started_at' => 'date',
            'expected_completion_at' => 'date',
            'completed_at' => 'date',
            'last_activity_at' => 'datetime',
            'next_follow_up_at' => 'date',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipProgram::class, 'discipleship_program_id');
    }

    public function discipler(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'discipler_profile_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function chapterProgress(): HasMany
    {
        return $this->hasMany(MemberChapterProgress::class);
    }

    public function completedUnits(): int
    {
        $rows = $this->relationLoaded('chapterProgress') ? $this->chapterProgress : $this->chapterProgress()->get();

        return $rows->where('status', ProgressStatus::Completed)->count();
    }

    public function percent(): int
    {
        if ($this->status === ProgressStatus::Completed) {
            return 100;
        }
        $total = $this->program?->totalUnits() ?? 0;

        return $total > 0 ? (int) round($this->completedUnits() / $total * 100) : 0;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProgressStatus::InProgress->value);
    }
}
