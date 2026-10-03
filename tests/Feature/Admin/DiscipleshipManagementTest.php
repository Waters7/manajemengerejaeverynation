<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\LeadershipStage;
use App\Enums\ParticipantStatus;
use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Models\ClassBatch;
use App\Models\DiscipleshipProgram;
use App\Models\LeadershipCandidate;
use App\Models\Profile;
use App\Models\User;
use App\Services\JourneyService;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscipleshipManagementTest extends TestCase
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

    public function test_completing_a_class_participant_completes_the_program_in_the_journey(): void
    {
        $pastor = $this->userWithRole(Role::Pastor);
        $profile = Profile::factory()->create();
        $batch = ClassBatch::create([
            'discipleship_program_id' => DiscipleshipProgram::where('slug', 'preparing-for-victory')->value('id'),
            'name' => 'Batch Oktober 2026',
            'status' => BatchStatus::Ongoing,
            'registration_status' => 'open',
        ]);

        $this->actingAs($pastor)->post(route('admin.classes.participants.store', $batch), ['profile_ids' => [$profile->id]])->assertSessionHasNoErrors();
        $participant = $batch->participants()->sole();

        $this->actingAs($pastor)->patch(route('admin.classes.participants.update', $participant), ['status' => 'completed']);

        $this->assertSame(ParticipantStatus::Completed, $participant->fresh()->status);
        $this->assertSame(ProgressStatus::Completed, $profile->programProgress()->where('discipleship_program_id', $batch->discipleship_program_id)->value('status'));
    }

    public function test_leader_cannot_approve_a_leader_but_pastor_can(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $pastor = $this->userWithRole(Role::Pastor);
        $candidate = LeadershipCandidate::create(['profile_id' => $leader->profile->id, 'stage' => LeadershipStage::Ready]);

        $this->actingAs($leader)->patch(route('admin.leadership.update', $candidate), ['stage' => 'approved'])->assertForbidden();
        $this->assertSame(LeadershipStage::Ready, $candidate->fresh()->stage);

        $this->actingAs($pastor)->patch(route('admin.leadership.update', $candidate), ['stage' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(LeadershipStage::Approved, $candidate->fresh()->stage);
        $this->assertSame($pastor->id, $candidate->fresh()->decided_by);
    }

    public function test_discipler_cannot_be_assigned_from_their_own_downline(): void
    {
        $pastor = $this->userWithRole(Role::Pastor);
        [$top, $bottom] = Profile::factory()->count(2)->create();

        $this->actingAs($pastor)->post(route('admin.relationships.store'), ['disciple_profile_id' => $bottom->id, 'discipler_profile_id' => $top->id]);
        $this->actingAs($pastor)->post(route('admin.relationships.store'), ['disciple_profile_id' => $top->id, 'discipler_profile_id' => $bottom->id])
            ->assertStatus(422);
    }

    public function test_plain_member_who_disciples_someone_can_update_their_progress_but_not_strangers(): void
    {
        $discipler = $this->userWithRole(Role::User);
        $disciple = Profile::factory()->create();
        $stranger = Profile::factory()->create();
        app(JourneyService::class)->assignDiscipler($disciple, $discipler->profile);

        $this->actingAs($discipler)->get(route('member.disciples.show', $disciple))->assertOk();
        $this->actingAs($discipler)->get(route('member.disciples.show', $stranger))->assertForbidden();
    }
}
