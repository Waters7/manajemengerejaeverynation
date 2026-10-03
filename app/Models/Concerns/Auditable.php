<?php

namespace App\Models\Concerns;

use App\Enums\AuditAction;
use App\Services\AuditLogger;

/**
 * Writes create / edit / delete events to audit_logs.
 *
 * Models may define `protected array $auditExclude` for columns that must never be
 * copied into the log (e.g. encrypted pastoral content).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => app(AuditLogger::class)->log(AuditAction::Create, $model, null, null, $model->auditValues($model->getAttributes())));

        static::updated(function ($model) {
            $changes = $model->auditValues($model->getChanges());
            unset($changes['updated_at']);
            if ($changes === []) {
                return;
            }
            $original = array_intersect_key($model->auditValues($model->getOriginal()), $changes);
            app(AuditLogger::class)->log(AuditAction::Update, $model, null, $original, $changes);
        });

        static::deleted(fn ($model) => app(AuditLogger::class)->log(AuditAction::Delete, $model));
    }

    protected function auditValues(array $values): array
    {
        $excluded = array_merge(['password', 'remember_token', 'created_at'], $this->auditExclude ?? [], $this->getHidden());

        return collect($values)
            ->except($excluded)
            ->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : (is_scalar($value) || $value === null ? $value : (string) json_encode($value)))
            ->all();
    }
}
