<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SermonSeries extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'cover_path'];

    public function sermons(): HasMany
    {
        return $this->hasMany(Sermon::class)->orderByDesc('preached_on');
    }
}
