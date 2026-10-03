<?php

namespace Tests\Feature\Site;

use App\Enums\JoinRequestStatus;
use App\Enums\LifeGroupCategory;
use App\Enums\Role;
use App\Models\LifeGroup;
use App\Models\LifeGroupJoinRequest;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifeGroupJoinTest extends TestCase
{
    use RefreshDatabase;

    private LifeGroup $group;

    private User $leader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->leader = User::factory()->create();
        $this->leader->assignRole(Role::Leader->value);
        $leaderProfile = Profile::factory()->create(['user_id' => $this->leader->id]);

        $this->group = LifeGroup::create([
            'name' => 'Bekasi Barat YP',
            'category' => LifeGroupCategory::YoungProfessionals,
            'leader_profile_id' => $leaderProfile->id,
            'whatsapp_invite_url' => 'https://chat.whatsapp.com/SECRETINVITE123',
            'accepting_members' => true,
            'is_public' => true,
            'status' => 'active',
        ]);
    }

    public function test_public_page_never_reveals_the_whatsapp_invite_link(): void
    {
        $this->get(route('lifegroups.show', $this->group->slug))
            ->assertOk()
            ->assertSee('Join this LifeGroup')
            ->assertDontSee('SECRETINVITE123');
    }

    public function test_join_form_creates_a_pending_request(): void
    {
        $this->post(route('lifegroups.join', $this->group->slug), ['name' => 'Kevin', 'whatsapp' => '081211112222'])
            ->assertRedirect(route('lifegroups.show', $this->group->slug));

        $request = LifeGroupJoinRequest::sole();
        $this->assertSame(JoinRequestStatus::Pending, $request->status);
        $this->assertSame('6281211112222', $request->whatsapp);
    }

    public function test_leader_cannot_share_the_invite_before_approval(): void
    {
        $request = $this->group->joinRequests()->create(['name' => 'Kevin', 'whatsapp' => '6281211112222', 'status' => JoinRequestStatus::Pending]);

        $this->actingAs($this->leader)
            ->get(route('admin.join-requests.invite', $request))
            ->assertForbidden();
    }

    public function test_approved_request_redirects_leader_to_whatsapp_with_the_invite(): void
    {
        $request = $this->group->joinRequests()->create(['name' => 'Kevin', 'whatsapp' => '6281211112222', 'status' => JoinRequestStatus::Approved]);

        $response = $this->actingAs($this->leader)->get(route('admin.join-requests.invite', $request));

        $response->assertRedirectContains('https://wa.me/6281211112222');
        $this->assertStringContainsString(rawurlencode('SECRETINVITE123'), $response->headers->get('Location'));
        $this->assertNotNull($request->fresh()->invite_shared_at);
    }

    public function test_marking_request_joined_adds_the_person_to_the_lifegroup(): void
    {
        $this->post(route('lifegroups.join', $this->group->slug), ['name' => 'Kevin', 'whatsapp' => '081211112222']);
        $request = LifeGroupJoinRequest::sole();

        $this->actingAs($this->leader)
            ->patch(route('admin.join-requests.update', $request), ['status' => 'joined'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->group->activeMembers()->whereKey($request->profile_id)->exists());
    }
}
