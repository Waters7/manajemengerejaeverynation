<?php

namespace App\Models;

use App\Enums\JoinRequestStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LifeGroupJoinRequest extends Model
{
    use Auditable;

    protected $fillable = [
        'life_group_id', 'profile_id', 'name', 'whatsapp', 'email', 'area', 'age', 'notes', 'status', 'handled_by',
        'contacted_at', 'approved_at', 'joined_at', 'invite_shared_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JoinRequestStatus::class,
            'contacted_at' => 'datetime',
            'approved_at' => 'datetime',
            'joined_at' => 'datetime',
            'invite_shared_at' => 'datetime',
        ];
    }

    public function lifeGroup(): BelongsTo
    {
        return $this->belongsTo(LifeGroup::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function contactNotes(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'notable')->latest();
    }

    /** WhatsApp group invitation is only revealed after approval. */
    public function canShareInvite(): bool
    {
        return in_array($this->status, [JoinRequestStatus::Approved, JoinRequestStatus::Joined], true);
    }
}
