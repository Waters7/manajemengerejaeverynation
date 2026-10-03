<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\LifeGroupRole;
use App\Enums\LifeStage;
use App\Enums\MemberStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A person known to the church — visitor, newcomer, member or leader.
 * May or may not have a login account (`user_id`).
 */
class Profile extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'full_name', 'nickname', 'gender', 'birth_date', 'whatsapp', 'email', 'address', 'area',
        'occupation', 'company', 'campus_id', 'school_name', 'life_stage', 'photo_path', 'join_date',
        'first_visit_date', 'source', 'member_status', 'current_stage_id', 'current_program_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'first_visit_date' => 'date',
            'gender' => Gender::class,
            'life_stage' => LifeStage::class,
            'member_status' => MemberStatus::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function newcomer(): HasOne
    {
        return $this->hasOne(Newcomer::class);
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipStage::class, 'current_stage_id');
    }

    public function currentProgram(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipProgram::class, 'current_program_id');
    }

    public function lifeGroupMemberships(): HasMany
    {
        return $this->hasMany(LifeGroupMember::class);
    }

    public function lifeGroups(): BelongsToMany
    {
        return $this->belongsToMany(LifeGroup::class, 'life_group_members')
            ->withPivot(['role', 'status', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeLifeGroups(): BelongsToMany
    {
        return $this->lifeGroups()->wherePivot('status', 'active');
    }

    public function ledLifeGroups(): HasMany
    {
        return $this->hasMany(LifeGroup::class, 'leader_profile_id');
    }

    public function programProgress(): HasMany
    {
        return $this->hasMany(MemberProgramProgress::class);
    }

    /** Relationships where this person is the disciple. */
    public function disciplerRelationships(): HasMany
    {
        return $this->hasMany(DisciplerRelationship::class, 'disciple_profile_id');
    }

    /** Relationships where this person is the discipler. */
    public function discipleRelationships(): HasMany
    {
        return $this->hasMany(DisciplerRelationship::class, 'discipler_profile_id');
    }

    public function activeDiscipler(): HasOne
    {
        return $this->hasOne(DisciplerRelationship::class, 'disciple_profile_id')
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function classParticipations(): HasMany
    {
        return $this->hasMany(ClassParticipant::class);
    }

    public function ministryMemberships(): HasMany
    {
        return $this->hasMany(MinistryMember::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function involvementRequests(): HasMany
    {
        return $this->hasMany(InvolvementRequest::class);
    }

    public function followUpTasks(): HasMany
    {
        return $this->hasMany(FollowUpTask::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(TimelineEntry::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }

    public function leadershipCandidate(): HasOne
    {
        return $this->hasOne(LeadershipCandidate::class);
    }

    public function prayerRequests(): HasMany
    {
        return $this->hasMany(PrayerRequest::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function displayName(): string
    {
        return $this->nickname ?: $this->full_name;
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->full_name))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function primaryLifeGroup(): ?LifeGroup
    {
        return $this->activeLifeGroups->first();
    }

    public function isLifeGroupLeader(): bool
    {
        return $this->ledLifeGroups()->exists()
            || $this->lifeGroupMemberships()
                ->where('status', 'active')
                ->whereIn('role', [LifeGroupRole::Leader->value, LifeGroupRole::CoLeader->value])
                ->exists();
    }

    public function nextBirthday(): ?Carbon
    {
        if (! $this->birth_date) {
            return null;
        }
        $next = $this->birth_date->copy()->year(now()->year);
        if ($next->lt(today())) {
            $next->addYear();
        }

        return $next;
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }
        $digits = preg_replace('/\D/', '', $term);

        return $query->where(function (Builder $q) use ($term, $digits) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('nickname', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
            if (strlen($digits) >= 4) {
                $q->orWhere('whatsapp', 'like', '%'.ltrim($digits, '0').'%');
            }
        });
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->whereIn('member_status', [MemberStatus::Connected->value, MemberStatus::Member->value]);
    }
}
