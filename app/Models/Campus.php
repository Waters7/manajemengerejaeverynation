<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use Auditable, HasSlug;

    protected $fillable = ['name', 'short_name', 'slug', 'description', 'address', 'cover_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function lifeGroups(): HasMany
    {
        return $this->hasMany(LifeGroup::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function ministers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
