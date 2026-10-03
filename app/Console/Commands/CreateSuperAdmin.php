<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\SuperAdminProvisioner;
use Illuminate\Console\Command;

/**
 * Create or repair the Super Admin from ADMIN_EMAIL / ADMIN_PASSWORD (useful on hosting without a terminal,
 * e.g. as a one-off cron job).
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'church:admin
        {--reset-password : Also set the password of an existing admin to ADMIN_PASSWORD}
        {--deactivate=* : Login(s) of other admin accounts to deactivate (e.g. an accidental duplicate)}';

    protected $description = 'Create or repair the Super Admin account from ADMIN_EMAIL / ADMIN_PASSWORD';

    public function handle(SuperAdminProvisioner $admins): int
    {
        $result = $admins->ensure(resetPassword: (bool) $this->option('reset-password'));
        $login = $result['user']->email;

        if ($result['created']) {
            $this->info("Super admin “{$login}” created.");
        } else {
            $this->info("Super admin “{$login}” is active".($this->option('reset-password') && filled(config('church.admin_password')) ? ' and its password was reset from ADMIN_PASSWORD.' : '.'));
        }

        if ($result['generated_password']) {
            $this->warn("ADMIN_PASSWORD is empty — generated password: {$result['generated_password']}");
        }

        foreach ((array) $this->option('deactivate') as $other) {
            $account = User::where('email', $other)->first();
            if (! $account || $account->is($result['user'])) {
                $this->line("Skipped “{$other}” (not found or the main admin).");

                continue;
            }
            $account->forceFill(['account_status' => AccountStatus::Inactive])->save();
            $account->syncRoles([Role::User->value]);
            $this->info("Account “{$other}” deactivated and its admin role removed.");
        }

        return self::SUCCESS;
    }
}
