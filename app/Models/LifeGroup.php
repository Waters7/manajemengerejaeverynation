<?php

namespace App\Models;

use App\Enums\JoinRequestStatus;
use App\Enums\LifeGroupCategory;
use App\Enums\LifeGroupRole;
use App\Enums\Weekday;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LifeGroup extends Model
{
    use Auditable, HasSlug, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category', 'area', 'meeting_day', 'meeting_time', 'location', 'description', 'cover_path',
        'leader_profile_id', 'co_leader_profile_id', 'campus_id', 'parent_id', 'accepting_members', 'is_public',
        'whatsapp_invite_url', 'capacity', 'status', 'launched_at',
    ];

    /** The invite link must never leak into public JSON / views. */
    protected $hidden = ['whatsapp_invite_url'];

    protected function casts(): array
    {
        return [
            'category' => LifeGroupCategory::class,
            'meeting_day' => Weekday::class,
            'accepting_members' => 'boolean',
            'is_public' => 'boolean',
            'launched_at' => 'date',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'leader_profile_id');
    }

    public function coLeader(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'co_leader_profile_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(LifeGroupMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'life_group_members')
            ->withPivot(['id', 'role', 'status', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(LifeGroupJoinRequest::class);
    }

    public function pendingJoinRequests(): HasMany
    {
        return $this->joinRequests()->whereIn('status', [JoinRequestStatus::Pending->value, JoinRequestStatus::Contacted->value, JoinRequestStatus::Approved->value]);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(LifeGroupMeeting::class)->orderByDesc('meeting_date');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->active()->where('is_public', true);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function scheduleLabel(): string
    {
        return trim(($this->meeting_day?->label() ?? '').' '.($this->meeting_time ? substr($this->meeting_time, 0, 5).' WIB' : ''));
    }

    /** Profile ids of the leadership team (leader, co-leader, apprentices with leader role). */
    public function leaderProfileIds(): array
    {
        $ids = $this->memberships()
            ->where('status', 'active')
            ->whereIn('role', [LifeGroupRole::Leader->value, LifeGroupRole::CoLeader->value])
            ->pluck('profile_id')
            ->all();

        return array_values(array_unique(array_filter([$this->leader_profile_id, $this->co_leader_profile_id, ...$ids])));
    }
}
