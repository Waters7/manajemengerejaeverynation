<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscipleshipStage extends Model
{
    use Auditable, HasSlug;

    protected $fillable = ['name', 'slug', 'tagline', 'description', 'color', 'sequence', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(DiscipleshipProgram::class)->orderBy('sequence');
    }

    public function activePrograms(): HasMany
    {
        return $this->programs()->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sequence');
    }
}
