<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Devotional extends Model
{
    use Auditable, HasSlug, Publishable, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'verse', 'bible_reference', 'opening', 'body', 'reflection', 'application', 'prayer', 'author',
        'devotional_date', 'cover_path', 'status', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['devotional_date' => 'date'];
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
