<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentStatus;
use App\Enums\LifeGroupCategory;
use App\Enums\PrayerStatus;
use App\Enums\PrayerVisibility;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerStatus;
use App\Models\Event;
use App\Models\LifeGroup;
use App\Models\Ministry;
use App\Models\MinistryMember;
use App\Models\PastoralCareRequest;
use App\Models\PrayerRequest;
use App\Models\Profile;
use App\Models\User;
use App\Models\VolunteerApplication;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CareAndEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Profile::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function eventWithCapacity(int $capacity): Event
    {
        return Event::create([
            'title' => 'Campus Night',
            'starts_at' => now()->addWeek(),
            'capacity' => $capacity,
            'registration_enabled' => true,
            'waiting_list_enabled' => true,
            'status' => ContentStatus::Published,
        ]);
    }

    public function test_leader_sees_lifegroup_prayers_but_never_pastor_only_ones(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $group = LifeGroup::create(['name' => 'LG', 'category' => LifeGroupCategory::Mixed, 'leader_profile_id' => $leader->profile->id, 'status' => 'active']);
        $shared = PrayerRequest::create(['life_group_id' => $group->id, 'request' => 'Shared with leader', 'visibility' => PrayerVisibility::LifegroupLeader, 'status' => PrayerStatus::New]);
        $private = PrayerRequest::create(['life_group_id' => $group->id, 'request' => 'Only for pastors', 'visibility' => PrayerVisibility::PastorOnly, 'status' => PrayerStatus::New]);

        $this->actingAs($leader)->get(route('admin.prayer-requests.index'))->assertSee('Shared with leader')->assertDontSee('Only for pastors');
        $this->actingAs($leader)->get(route('admin.prayer-requests.show', $private))->assertNotFound();
        $this->actingAs($leader)->get(route('admin.prayer-requests.show', $shared))->assertOk();
    }

    public function test_prayer_text_is_encrypted_at_rest(): void
    {
        $prayer = PrayerRequest::create(['request' => 'Healing for my mother', 'visibility' => PrayerVisibility::PastorOnly, 'status' => PrayerStatus::New]);

        $this->assertStringNotContainsString('Healing', (string) DB::table('prayer_requests')->where('id', $prayer->id)->value('request'));
        $this->assertSame('Healing for my mother', $prayer->fresh()->request);
    }

    public function test_pastoral_care_is_hidden_from_non_pastoral_roles(): void
    {
        $care = PastoralCareRequest::create(['subject' => 'Family matter', 'description' => 'Sensitive details', 'status' => 'open', 'priority' => 'normal']);

        foreach ([Role::Leader, Role::CampusMinistry, Role::MinistryCoordinator, Role::WelcomeTeam] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('admin.pastoral-care.show', $care))->assertForbidden();
        }
        $this->actingAs($this->userWithRole(Role::Pastor))->get(route('admin.pastoral-care.show', $care))->assertOk()->assertSee('Sensitive details');
        $this->assertDatabaseMissing('audit_logs', ['new_values' => json_encode(['description' => 'Sensitive details'])]);
    }

    public function test_registration_beyond_capacity_goes_to_the_waiting_list(): void
    {
        $event = $this->eventWithCapacity(1);

        $this->post(route('events.register', $event->slug), ['name' => 'First', 'whatsapp' => '081211110001']);
        $this->post(route('events.register', $event->slug), ['name' => 'Second', 'whatsapp' => '081211110002']);

        $this->assertSame(RegistrationStatus::Registered, $event->registrations()->where('name', 'First')->value('status'));
        $this->assertSame(RegistrationStatus::WaitingList, $event->registrations()->where('name', 'Second')->value('status'));
    }

    public function test_cancelling_a_seat_promotes_the_waiting_list(): void
    {
        $event = $this->eventWithCapacity(1);
        $this->post(route('events.register', $event->slug), ['name' => 'First', 'whatsapp' => '081211110001']);
        $this->post(route('events.register', $event->slug), ['name' => 'Second', 'whatsapp' => '081211110002']);
        $pastor = $this->userWithRole(Role::Pastor);

        $this->actingAs($pastor)->patch(route('admin.events.registrations.update', $event->registrations()->where('name', 'First')->first()), ['action' => 'cancel']);

        $this->assertSame(RegistrationStatus::Registered, $event->registrations()->where('name', 'Second')->value('status'));
    }

    public function test_check_in_by_ticket_code_marks_attendance(): void
    {
        $event = $this->eventWithCapacity(10);
        $this->post(route('events.register', $event->slug), ['name' => 'Guest', 'whatsapp' => '081211110003']);
        $registration = $event->registrations()->sole();

        $this->actingAs($this->userWithRole(Role::Pastor))
            ->post(route('admin.events.check-in.store', $event), ['code' => strtolower($registration->code)])
            ->assertSessionHas('status');

        $this->assertNotNull($registration->fresh()->checked_in_at);
    }

    public function test_accepting_a_volunteer_adds_them_to_the_ministry_without_a_system_role(): void
    {
        $coordinator = $this->userWithRole(Role::MinistryCoordinator);
        $ministry = Ministry::where('slug', 'multimedia')->firstOrFail();
        $ministry->update(['coordinator_id' => $coordinator->id]);
        $applicant = User::factory()->create();
        $applicant->assignRole(Role::User->value);
        $profile = Profile::factory()->create(['user_id' => $applicant->id]);
        $application = VolunteerApplication::create(['profile_id' => $profile->id, 'name' => $profile->full_name, 'whatsapp' => '6281200000000', 'status' => VolunteerApplicationStatus::Interview]);
        $application->ministries()->attach($ministry);

        $this->actingAs($coordinator)
            ->patch(route('admin.volunteer-applications.update', $application), ['status' => 'accepted'])
            ->assertSessionHasNoErrors();

        $this->assertSame(VolunteerStatus::Active, MinistryMember::where('profile_id', $profile->id)->value('status'));
        $this->assertSame([Role::User->value], $applicant->fresh()->getRoleNames()->all());
    }

    public function test_coordinator_cannot_manage_another_coordinators_ministry(): void
    {
        $coordinator = $this->userWithRole(Role::MinistryCoordinator);
        $other = Ministry::where('slug', 'worship')->firstOrFail();

        $this->actingAs($coordinator)->get(route('admin.ministries.show', $other))->assertForbidden();
    }
}
