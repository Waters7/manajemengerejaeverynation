<?php

namespace App\Enums;

enum AuditAction: string
{
    use Concerns;

    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Publish = 'publish';
    case Approve = 'approve';
    case Reject = 'reject';
    case Assign = 'assign';
    case RoleChange = 'role_change';
    case Login = 'login';
    case Export = 'export';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Create',
            self::Update => 'Edit',
            self::Delete => 'Delete',
            self::Publish => 'Publish',
            self::Approve => 'Approve',
            self::Reject => 'Reject',
            self::Assign => 'Assign',
            self::RoleChange => 'Role Change',
            self::Login => 'Login',
            self::Export => 'Export',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Create => 'green',
            self::Update => 'blue',
            self::Delete => 'red',
            self::Publish => 'green',
            self::Approve => 'green',
            self::Reject => 'amber',
            self::Assign => 'indigo',
            self::RoleChange => 'red',
            self::Login => 'gray',
            self::Export => 'gray',
        };
    }
}
