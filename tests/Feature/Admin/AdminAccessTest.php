<?php

namespace Tests\Feature\Admin;

use App\Enums\LifeGroupCategory;
use App\Enums\Role;
use App\Models\Campus;
use App\Models\ClassBatch;
use App\Models\Devotional;
use App\Models\Event;
use App\Models\InvolvementRequest;
use App\Models\LifeGroup;
use App\Models\LifeGroupMeeting;
use App\Models\Ministry;
use App\Models\PrayerRequest;
use App\Models\Profile;
use App\Models\Sermon;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Services\ReportService;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
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
        Profile::factory()->create(['user_id' => $user->id, 'full_name' => $user->name]);

        return $user->fresh();
    }

    private function groupLedBy(User $leader, string $name): LifeGroup
    {
        return LifeGroup::create([
            'name' => $name,
            'category' => LifeGroupCategory::Mixed,
            'leader_profile_id' => $leader->profile->id,
            'status' => 'active',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_plain_user_is_sent_to_member_dashboard(): void
    {
        $this->actingAs($this->userWithRole(Role::User))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('member.dashboard'));
    }

    public function test_leader_can_open_own_lifegroup_but_not_another(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $other = $this->userWithRole(Role::Leader);
        $own = $this->groupLedBy($leader, 'Own Group');
        $foreign = $this->groupLedBy($other, 'Foreign Group');

        $this->actingAs($leader)->get(route('admin.lifegroups.show', $own))->assertOk();
        $this->actingAs($leader)->get(route('admin.lifegroups.show', $foreign))->assertForbidden();
        $this->actingAs($leader)->get(route('admin.lifegroups.index'))->assertSee('Own Group')->assertDontSee('Foreign Group');
    }

    public function test_leader_cannot_view_a_person_outside_their_care(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $stranger = Profile::factory()->create();

        $this->actingAs($leader)->get(route('admin.members.show', $stranger))->assertForbidden();
    }

    public function test_leader_cannot_open_pastoral_care(): void
    {
        $this->actingAs($this->userWithRole(Role::Leader))
            ->get(route('admin.pastoral-care.index'))
            ->assertForbidden();
    }

    public function test_only_role_managers_can_change_roles(): void
    {
        $pastor = $this->userWithRole(Role::Pastor);
        $target = $this->userWithRole(Role::User);

        $this->actingAs($pastor)
            ->put(route('admin.users.roles.update', $target), ['roles' => [Role::Leader->value]])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole(Role::Leader->value));
    }

    public function test_role_change_is_written_to_the_audit_log(): void
    {
        $admin = $this->userWithRole(Role::SuperAdmin);
        $target = $this->userWithRole(Role::User);

        $this->actingAs($admin)
            ->put(route('admin.users.roles.update', $target), ['roles' => [Role::Leader->value]])
            ->assertSessionHasNoErrors();

        $this->assertTrue($target->fresh()->hasRole(Role::Leader->value));
        $this->assertDatabaseHas('audit_logs', ['action' => 'role_change', 'auditable_id' => $target->id, 'user_id' => $admin->id]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminPages(): array
    {
        return collect([
            'admin.dashboard', 'admin.members.index', 'admin.members.create', 'admin.newcomers.index', 'admin.involvement.index',
            'admin.follow-ups.index', 'admin.lifegroups.index', 'admin.lifegroups.create', 'admin.join-requests.index',
            'admin.meetings.index', 'admin.birthdays.index', 'admin.users.index', 'admin.roles.index', 'admin.notifications.index',
            'admin.journey.index', 'admin.one2one.index', 'admin.curriculum.index', 'admin.curriculum.programs.create', 'admin.books.index',
            'admin.classes.index', 'admin.classes.create', 'admin.victory-weekend.index', 'admin.disciplers.index', 'admin.disciplers.tree',
            'admin.leadership.index',
            'admin.ministries.index', 'admin.ministries.create', 'admin.volunteers.index', 'admin.volunteer-applications.index',
            'admin.serving.index', 'admin.campuses.index', 'admin.campuses.create',
            'admin.events.index', 'admin.events.create', 'admin.homepage.edit', 'admin.devotionals.index', 'admin.devotionals.create',
            'admin.sermons.index', 'admin.sermons.create', 'admin.galleries.index', 'admin.galleries.create', 'admin.pages.index', 'admin.pages.create',
            'admin.prayer-requests.index', 'admin.pastoral-care.index', 'admin.pastoral-care.create',
            'admin.announcements.index', 'admin.announcements.create', 'admin.reports.index',
            'admin.media.index', 'admin.settings.edit', 'admin.interests.index', 'admin.audit-logs.index',
        ])->mapWithKeys(fn ($route) => [$route => [$route]])->all();
    }

    #[DataProvider('adminPages')]
    public function test_ministry_dashboard_pages_render_with_demo_data(string $route): void
    {
        $this->seed(DemoSeeder::class);
        $admin = User::role(Role::SuperAdmin->value)->first() ?? $this->userWithRole(Role::SuperAdmin);

        $this->actingAs($admin)->get(route($route))->assertOk();
    }

    public function test_every_report_renders_and_exports(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = $this->userWithRole(Role::SuperAdmin);

        foreach (array_keys(ReportService::catalog()) as $report) {
            $this->actingAs($admin)->get(route('admin.reports.show', $report))->assertOk();
            $this->actingAs($admin)->get(route('admin.reports.export', [$report, 'format' => 'csv']))->assertOk()->assertDownload();
        }
    }

    public function test_detail_pages_render_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = $this->userWithRole(Role::SuperAdmin);
        $member = Profile::whereHas('programProgress')->whereHas('lifeGroupMemberships')->firstOrFail();
        $progress = $member->programProgress()->firstOrFail();
        $batch = ClassBatch::firstOrFail();

        $pages = [
            route('admin.members.show', $member),
            route('admin.members.edit', $member),
            route('admin.lifegroups.show', $member->lifeGroupMemberships()->value('life_group_id')),
            route('admin.lifegroups.edit', $member->lifeGroupMemberships()->value('life_group_id')),
            route('admin.involvement.show', InvolvementRequest::firstOrFail()),
            route('admin.progress.show', $progress),
            route('admin.classes.show', $batch),
            route('admin.classes.edit', $batch),
            route('admin.classes.sessions.show', $batch->sessions()->firstOrFail()),
            route('admin.curriculum.programs.edit', $progress->discipleship_program_id),
            route('admin.meetings.create', $member->lifeGroupMemberships()->value('life_group_id')),
            route('admin.meetings.edit', LifeGroupMeeting::firstOrFail()),
            route('admin.users.edit', $admin),
            route('admin.search', ['q' => mb_substr($member->full_name, 0, 4)]),
            route('admin.ministries.show', Ministry::firstOrFail()),
            route('admin.ministries.edit', Ministry::firstOrFail()),
            route('admin.volunteer-applications.show', VolunteerApplication::firstOrFail()),
            route('admin.campuses.show', Campus::firstOrFail()),
            route('admin.campuses.edit', Campus::firstOrFail()),
            route('admin.events.show', Event::firstOrFail()),
            route('admin.events.edit', Event::firstOrFail()),
            route('admin.events.check-in', Event::where('registration_enabled', true)->firstOrFail()),
            route('admin.devotionals.edit', Devotional::firstOrFail()),
            route('admin.sermons.edit', Sermon::firstOrFail()),
            route('admin.prayer-requests.show', PrayerRequest::firstOrFail()),
            route('admin.disciplers.tree', ['root' => $member->id]),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
