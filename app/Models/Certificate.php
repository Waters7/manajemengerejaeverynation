<?php

namespace App\Models;

use App\Enums\CertificateKind;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A certificate in a person's account: an uploaded baptism certificate, or a one-time
 * program certificate (Leadership 113 / 215) issued when the program is completed.
 */
class Certificate extends Model
{
    use Auditable;

    protected $fillable = [
        'profile_id', 'type', 'discipleship_program_id', 'certificate_number', 'title', 'issued_at',
        'file_path', 'file_name', 'mime_type', 'notes', 'issued_by',
    ];

    protected function casts(): array
    {
        return ['type' => CertificateKind::class, 'issued_at' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipProgram::class, 'discipleship_program_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }
}
