<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(AuditAction $action, ?Model $model = null, ?string $description = null, ?array $old = null, ?array $new = null, ?int $userId = null): ?AuditLog
    {
        if ($model instanceof AuditLog) {
            return null;
        }

        $request = app()->runningInConsole() ? null : request();

        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'description' => $description ?? ($model ? class_basename($model).' #'.$model->getKey() : null),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }
}
