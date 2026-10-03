<?php

namespace Tests\Feature\Seeders;

use App\Enums\Role;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);
    }

    public function test_production_without_demo_password_skips_demo_accounts(): void
    {
        $this->app['env'] = 'production';
        config(['church.demo_password' => null]);

        $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(0, User::count());
        $this->assertSame(0, Profile::count());
    }

    public function test_production_demo_accounts_use_the_configured_password(): void
    {
        $this->app['env'] = 'production';
        config(['church.demo_password' => 'Gembala-Bekasi-2026']);

        $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true])->assertSuccessful();

        $pastor = User::where('email', 'pastor@everynationbekasi.test')->firstOrFail();
        $this->assertTrue(Hash::check('Gembala-Bekasi-2026', $pastor->password));
        $this->assertFalse(Hash::check('password', $pastor->password));
    }

    public function test_seeding_twice_in_production_keeps_a_single_configured_admin(): void
    {
        $this->app['env'] = 'production';
        config(['church.admin_email' => 'admin', 'church.admin_password' => 'honor-god-2026', 'church.seed_demo' => false]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $admins = User::role(Role::SuperAdmin->value)->get();
        $this->assertCount(1, $admins);
        $this->assertSame('admin', $admins->first()->email);
        $this->assertTrue(Hash::check('honor-god-2026', $admins->first()->password));
    }

    public function test_admin_command_resets_the_password_from_config(): void
    {
        config(['church.admin_email' => 'admin', 'church.admin_password' => 'first-password-1']);
        $this->artisan('church:admin')->assertSuccessful();

        config(['church.admin_password' => 'second-password-2']);
        $this->artisan('church:admin', ['--reset-password' => true])->assertSuccessful();

        $this->assertTrue(Hash::check('second-password-2', User::where('email', 'admin')->value('password')));
    }

    public function test_admin_command_deactivates_an_accidental_duplicate_admin(): void
    {
        config(['church.admin_email' => 'admin', 'church.admin_password' => 'first-password-1']);
        $this->artisan('church:admin')->assertSuccessful();
        $duplicate = User::factory()->create(['email' => 'admin@everynationbekasi.test']);
        $duplicate->assignRole(Role::SuperAdmin->value);

        $this->artisan('church:admin', ['--deactivate' => ['admin@everynationbekasi.test']])->assertSuccessful();

        $duplicate->refresh();
        $this->assertFalse($duplicate->hasRole(Role::SuperAdmin->value));
        $this->assertFalse($duplicate->isActive());
        $this->assertTrue(User::where('email', 'admin')->firstOrFail()->hasRole(Role::SuperAdmin->value));
    }

    public function test_scheduled_tasks_run_in_process_without_proc_open(): void
    {
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();

        $this->assertNotEmpty($events);
        foreach ($events as $event) {
            $this->assertInstanceOf(\Illuminate\Console\Scheduling\CallbackEvent::class, $event, "{$event->description} would need proc_open");
        }
    }
}
