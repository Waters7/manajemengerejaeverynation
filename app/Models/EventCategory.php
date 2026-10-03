<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
