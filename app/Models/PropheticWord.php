<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recorded prophetic word for one person. The audio lives on the private disk and is only
 * streamed to that person and to staff allowed to manage prophetic words.
 */
class PropheticWord extends Model
{
    use Auditable;

    /** Personal and spiritual content stays out of the audit trail. */
    protected array $auditExclude = ['notes'];

    protected $fillable = ['profile_id', 'title', 'given_on', 'given_by', 'audio_path', 'audio_name', 'mime_type', 'size', 'notes', 'uploaded_by'];

    protected function casts(): array
    {
        return ['given_on' => 'date', 'notes' => 'encrypted'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
