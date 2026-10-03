<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonPoint extends Model
{
    protected $fillable = ['sermon_id', 'sequence', 'title', 'body'];

    public function sermon(): BelongsTo
    {
        return $this->belongsTo(Sermon::class);
    }
}
