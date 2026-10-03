<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\MemberStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Demo data is only added outside production (or when SEED_DEMO=true).
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);

        $this->createSuperAdmin();

        if (! app()->isProduction() || filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoSeeder::class);
        }
    }

    private function createSuperAdmin(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@everynationbekasi.test');
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $password = app()->isProduction() ? Str::password(16) : 'password';
            if (app()->isProduction()) {
                $this->command?->warn("Super admin {$email} created with generated password: {$password}");
            }
        }

        $admin = User::firstOrCreate(['email' => $email], [
            'name' => 'Administrator',
            'nickname' => 'Admin',
            'password' => $password,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles([Role::SuperAdmin->value]);

        $admin->profile()->firstOrCreate([], [
            'full_name' => $admin->name,
            'nickname' => $admin->nickname,
            'email' => filter_var($admin->email, FILTER_VALIDATE_EMAIL) ? $admin->email : null,
            'member_status' => MemberStatus::Member,
        ]);
    }
}
