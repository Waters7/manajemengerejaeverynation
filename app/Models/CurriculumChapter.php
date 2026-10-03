<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumChapter extends Model
{
    protected $fillable = ['discipleship_program_id', 'number', 'title', 'description', 'sequence'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipProgram::class, 'discipleship_program_id');
    }
}
