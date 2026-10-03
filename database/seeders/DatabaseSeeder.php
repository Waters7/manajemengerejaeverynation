<?php

namespace Database\Seeders;

use App\Services\SuperAdminProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Demo data is only added outside production (or when SEED_DEMO=true).
     * Settings come from config/church.php so seeding also works when the configuration is cached.
     */
    public function run(SuperAdminProvisioner $admins): void
    {
        $this->call([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);

        $result = $admins->ensure();
        if ($result['generated_password']) {
            $this->command?->warn("Super admin {$result['user']->email} created with generated password: {$result['generated_password']}");
        }

        if (! app()->isProduction() || config('church.seed_demo')) {
            $this->call(DemoSeeder::class);
        }
    }
}
