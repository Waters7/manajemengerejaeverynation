<?php

namespace App\Models;

use App\Enums\ProgramType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A curriculum item inside a 4E stage: a BOOK (One 2 One, Purple Book),
 * CLASS (Preparing for Victory, Making Disciples), TRAINING (Leadership 113/215)
 * or EVENT (Victory Weekend).
 */
class DiscipleshipProgram extends Model
{
    use Auditable, HasSlug;

    public const ONE2ONE_SLUG = 'one-2-one';

    public const VICTORY_WEEKEND_SLUG = 'victory-weekend';

    public const PREPARING_FOR_VICTORY_SLUG = 'preparing-for-victory';

    protected $fillable = [
        'discipleship_stage_id', 'name', 'slug', 'description', 'type', 'sequence', 'prerequisite_id',
        'is_required', 'is_milestone', 'total_sessions', 'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProgramType::class,
            'is_required' => 'boolean',
            'is_milestone' => 'boolean',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipStage::class, 'discipleship_stage_id');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prerequisite_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(CurriculumChapter::class)->orderBy('sequence')->orderBy('number');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ClassBatch::class)->orderByDesc('start_date');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(MemberProgramProgress::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public static function one2one(): ?self
    {
        return static::where('slug', self::ONE2ONE_SLUG)->first();
    }

    public function isBook(): bool
    {
        return $this->type === ProgramType::Book;
    }

    /** Total units used for progress: chapters for books, configured sessions otherwise. */
    public function totalUnits(): int
    {
        $chapters = $this->relationLoaded('chapters') ? $this->chapters->count() : $this->chapters()->count();

        return $chapters ?: (int) $this->total_sessions;
    }
}
