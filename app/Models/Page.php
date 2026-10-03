<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    use Auditable, HasSlug, Publishable;

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'cover_path', 'meta_description', 'status', 'published_at', 'created_by'];

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
