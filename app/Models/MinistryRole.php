<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinistryRole extends Model
{
    protected $fillable = ['ministry_id', 'name', 'description'];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }
}
