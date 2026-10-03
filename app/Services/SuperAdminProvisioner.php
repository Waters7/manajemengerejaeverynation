<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\MemberStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates (or repairs) the Super Admin account from config('church.admin_email' / 'admin_password').
 */
class SuperAdminProvisioner
{
    /**
     * @return array{user: User, created: bool, generated_password: ?string}
     */
    public function ensure(bool $resetPassword = false): array
    {
        $login = (string) config('church.admin_email');
        $password = config('church.admin_password');
        $generated = null;

        if (blank($password)) {
            $password = app()->isProduction() ? Str::password(16) : 'password';
            $generated = app()->isProduction() ? $password : null;
        }

        $user = User::withTrashed()->where('email', $login)->first();
        $created = false;

        if (! $user) {
            $user = (new User)->forceFill([
                'name' => 'Administrator',
                'nickname' => 'Admin',
                'email' => $login,
                'password' => $password,
                'account_status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ]);
            $user->save();
            $created = true;
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->forceFill(['account_status' => AccountStatus::Active]);
            if ($resetPassword && filled(config('church.admin_password'))) {
                $user->forceFill(['password' => config('church.admin_password')]);
            }
            $user->save();
        }

        $user->syncRoles([Role::SuperAdmin->value]);
        $user->profile()->firstOrCreate([], [
            'full_name' => $user->name,
            'nickname' => $user->nickname,
            'email' => filter_var($user->email, FILTER_VALIDATE_EMAIL) ? $user->email : null,
            'member_status' => MemberStatus::Member,
        ]);

        return ['user' => $user, 'created' => $created, 'generated_password' => $created ? $generated : null];
    }
}
