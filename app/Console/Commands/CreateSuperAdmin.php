<?php

namespace App\Console\Commands;

use App\Services\SuperAdminProvisioner;
use Illuminate\Console\Command;

/**
 * Create or repair the Super Admin from ADMIN_EMAIL / ADMIN_PASSWORD (useful on hosting without a terminal,
 * e.g. as a one-off cron job).
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'church:admin {--reset-password : Also set the password of an existing admin to ADMIN_PASSWORD}';

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

        return self::SUCCESS;
    }
}
