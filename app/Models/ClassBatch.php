<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Enums\ParticipantStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassBatch extends Model
{
    use Auditable;

    protected $fillable = [
        'discipleship_program_id', 'name', 'start_date', 'end_date', 'facilitator_profile_id', 'location', 'capacity',
        'registration_status', 'status', 'campus_id', 'notes',
    ];

    protected function casts(): array
    {
        return ['status' => BatchStatus::class, 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipProgram::class, 'discipleship_program_id');
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'facilitator_profile_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class)->orderBy('session_number');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ClassParticipant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->participants()->where('status', '!=', ParticipantStatus::Cancelled->value);
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->activeParticipants()->count() >= $this->capacity;
    }

    public function isOpenForRegistration(): bool
    {
        return $this->registration_status === 'open'
            && in_array($this->status, [BatchStatus::Planned, BatchStatus::Ongoing], true)
            && ! $this->isFull();
    }

    public function fullLabel(): string
    {
        return ($this->program?->name ? $this->program->name.' — ' : '').$this->name;
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereIn('status', [BatchStatus::Planned->value, BatchStatus::Ongoing->value]);
    }
}
