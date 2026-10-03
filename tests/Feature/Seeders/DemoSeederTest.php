<?php

namespace Tests\Feature\Seeders;

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

    protected function tearDown(): void
    {
        putenv('DEMO_PASSWORD');
        unset($_ENV['DEMO_PASSWORD'], $_SERVER['DEMO_PASSWORD']);
        parent::tearDown();
    }

    public function test_production_without_demo_password_skips_demo_accounts(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(0, User::count());
        $this->assertSame(0, Profile::count());
    }

    public function test_production_demo_accounts_use_the_configured_password(): void
    {
        $this->app['env'] = 'production';
        putenv('DEMO_PASSWORD=Gembala-Bekasi-2026');
        $_ENV['DEMO_PASSWORD'] = $_SERVER['DEMO_PASSWORD'] = 'Gembala-Bekasi-2026';

        $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true])->assertSuccessful();

        $pastor = User::where('email', 'pastor@everynationbekasi.test')->firstOrFail();
        $this->assertTrue(Hash::check('Gembala-Bekasi-2026', $pastor->password));
        $this->assertFalse(Hash::check('password', $pastor->password));
    }
}
