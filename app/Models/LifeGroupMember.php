<?php

namespace App\Models;

use App\Enums\LifeGroupRole;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeGroupMember extends Model
{
    use Auditable;

    protected $fillable = ['life_group_id', 'profile_id', 'role', 'status', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return ['role' => LifeGroupRole::class, 'joined_at' => 'date', 'left_at' => 'date'];
    }

    public function lifeGroup(): BelongsTo
    {
        return $this->belongsTo(LifeGroup::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
