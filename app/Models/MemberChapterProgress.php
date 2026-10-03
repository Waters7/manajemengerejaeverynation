<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberChapterProgress extends Model
{
    protected $table = 'member_chapter_progress';

    protected $fillable = ['member_program_progress_id', 'curriculum_chapter_id', 'status', 'completed_on', 'notes', 'next_follow_up_at', 'recorded_by'];

    protected function casts(): array
    {
        return ['status' => ProgressStatus::class, 'completed_on' => 'date', 'next_follow_up_at' => 'date'];
    }

    public function programProgress(): BelongsTo
    {
        return $this->belongsTo(MemberProgramProgress::class, 'member_program_progress_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(CurriculumChapter::class, 'curriculum_chapter_id');
    }
}
