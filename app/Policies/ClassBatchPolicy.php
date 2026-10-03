<?php

namespace App\Policies;

use App\Models\ClassBatch;
use App\Models\User;
use App\Services\AccessScope;

class ClassBatchPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function view(User $user, ClassBatch $batch): bool
    {
        return $user->can('classes.view')
            && $this->scope->classBatches(ClassBatch::whereKey($batch->id), $user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('classes.manage');
    }

    public function update(User $user, ClassBatch $batch): bool
    {
        if (! $user->can('classes.manage')) {
            return false;
        }

        return $this->scope->isChurchWide($user)
            || ($batch->campus_id && in_array($batch->campus_id, $this->scope->campusIds($user), true));
    }
}
