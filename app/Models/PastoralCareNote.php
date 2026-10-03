<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastoralCareNote extends Model
{
    protected $fillable = ['pastoral_care_request_id', 'user_id', 'body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(PastoralCareRequest::class, 'pastoral_care_request_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
