<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'name' => 'Daniel Wijaya',
            'nickname' => 'Daniel',
            'whatsapp' => '081298765432',
            'email' => 'daniel@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ];
    }

    public function test_public_sign_up_creates_pending_user_with_only_the_user_role(): void
    {
        $this->post(route('register.store'), $this->payload())
            ->assertRedirect(route('member.dashboard'));

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(AccountStatus::PendingVerification, $user->account_status);
        $this->assertSame([Role::User->value], $user->getRoleNames()->all());
        $this->assertFalse($user->canAccessAdmin());
        $this->assertSame('6281298765432', $user->profile->whatsapp);
    }

    public function test_sign_up_links_an_existing_connect_card_person_record(): void
    {
        $profile = Profile::factory()->newcomer()->create(['whatsapp' => '6281298765432', 'email' => null]);

        $this->post(route('register.store'), $this->payload());

        $this->assertSame(User::sole()->id, $profile->fresh()->user_id);
        $this->assertSame(1, Profile::count());
    }

    public function test_sign_up_ignores_a_submitted_role_field(): void
    {
        $this->post(route('register.store'), $this->payload() + ['roles' => [Role::SuperAdmin->value], 'account_status' => 'active']);

        $user = User::sole();
        $this->assertFalse($user->hasRole(Role::SuperAdmin->value));
        $this->assertSame(AccountStatus::PendingVerification, $user->account_status);
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        $user = User::factory()->create(['account_status' => AccountStatus::Inactive]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
