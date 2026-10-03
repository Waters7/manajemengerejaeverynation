<?php

namespace Tests\Feature\Admin;

use App\Enums\LifeGroupCategory;
use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Models\Certificate;
use App\Models\DiscipleshipProgram;
use App\Models\LifeGroup;
use App\Models\Profile;
use App\Models\PropheticWord;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\JourneyService;
use App\Services\LifeGroupService;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificatesAndPropheticWordsTest extends TestCase
{
    use RefreshDatabase;

    private JourneyService $journey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);
        Storage::fake('local');
        $this->journey = app(JourneyService::class);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Profile::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function program(string $slug): DiscipleshipProgram
    {
        return DiscipleshipProgram::where('slug', $slug)->firstOrFail();
    }

    private function complete(Profile $profile, string $slug, ?Profile $discipler = null): void
    {
        $progress = $this->journey->startProgram($profile, $this->program($slug), $discipler);
        $this->journey->completeProgram($progress->fresh());
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('baptism.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }

    private function mp3(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('word.mp3', 'ID3'."\x03\x00\x00\x00\x00\x00\x00".str_repeat("\xFF\xFB\x90\x64".str_repeat("\x00", 413), 6));
    }

    public function test_completing_leadership_113_issues_a_certificate_the_member_can_open(): void
    {
        $member = $this->userWithRole(Role::User);
        $this->complete($member->profile, 'leadership-113');

        $certificate = Certificate::sole();
        $this->assertStringStartsWith('ENB-L113-'.now()->year.'-', $certificate->certificate_number);

        $this->actingAs($member)->get(route('member.certificates'))->assertOk()->assertSee($certificate->certificate_number);
        $this->actingAs($member)->get(route('certificates.show', $certificate))->assertOk()->assertSee($member->profile->full_name);
    }

    public function test_another_member_cannot_open_someone_elses_certificate(): void
    {
        $owner = $this->userWithRole(Role::User);
        $this->complete($owner->profile, 'leadership-215');

        $this->actingAs($this->userWithRole(Role::User))
            ->get(route('certificates.show', Certificate::sole()))
            ->assertForbidden();
    }

    public function test_repeatable_programs_do_not_issue_certificates_but_are_counted(): void
    {
        $discipler = Profile::factory()->create();
        [$a, $b, $c] = Profile::factory()->count(3)->create();

        $this->complete($discipler, 'one-2-one');
        $this->complete($a, 'one-2-one', $discipler);
        $this->complete($b, 'one-2-one', $discipler);
        $this->journey->startProgram($c, $this->program('one-2-one'), $discipler);

        $record = app(CertificateService::class)->journeyRecords($discipler)->firstWhere('program.slug', 'one-2-one');

        $this->assertSame(0, Certificate::count());
        $this->assertSame(1, $record['self']);
        $this->assertSame(2, $record['led']);
        $this->assertSame(1, $record['leading']);
    }

    public function test_member_can_print_their_journey_record_with_counts(): void
    {
        $member = $this->userWithRole(Role::User);
        $this->complete($member->profile, 'purple-book');

        $this->actingAs($member)
            ->get(route('certificates.journey-record', [$member->profile, $this->program('purple-book')]))
            ->assertOk()
            ->assertSee('Discipleship Journey Record')
            ->assertSee('1×');
    }

    public function test_leader_uploads_a_baptism_certificate_the_member_can_download(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $member = $this->userWithRole(Role::User);
        $group = LifeGroup::create(['name' => 'LG', 'category' => LifeGroupCategory::Mixed, 'leader_profile_id' => $leader->profile->id, 'status' => 'active']);
        app(LifeGroupService::class)->addMember($group, $member->profile);

        $this->actingAs($leader)
            ->post(route('admin.members.certificates.store', $member->profile), ['type' => 'baptism', 'file' => $this->pdf()])
            ->assertSessionHasNoErrors();

        $certificate = Certificate::sole();
        Storage::disk('local')->assertExists($certificate->file_path);
        $this->actingAs($member)->get(route('certificates.file', $certificate))->assertOk();
        $this->actingAs($this->userWithRole(Role::User))->get(route('certificates.file', $certificate))->assertForbidden();
    }

    public function test_leader_cannot_upload_for_someone_outside_their_care(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $stranger = Profile::factory()->create();

        $this->actingAs($leader)
            ->post(route('admin.members.certificates.store', $stranger), ['type' => 'baptism', 'file' => $this->pdf()])
            ->assertForbidden();

        $this->assertSame(0, Certificate::count());
    }

    public function test_staff_uploads_a_prophetic_word_only_the_owner_and_staff_can_hear(): void
    {
        $pastor = $this->userWithRole(Role::Pastor);
        $member = $this->userWithRole(Role::User);

        $this->actingAs($pastor)
            ->post(route('admin.members.prophetic-words.store', $member->profile), ['title' => 'Word for the season', 'audio' => $this->mp3(), 'notes' => 'Private transcript'])
            ->assertSessionHasNoErrors();

        $word = PropheticWord::sole();
        $this->assertSame('Private transcript', $word->notes);
        $this->actingAs($member)->get(route('member.prophetic-words'))->assertOk()->assertSee('Word for the season');
        $this->actingAs($member)->get(route('prophetic-words.audio', $word))->assertOk();
        $this->actingAs($pastor)->get(route('prophetic-words.audio', $word))->assertOk();
        $this->actingAs($this->userWithRole(Role::User))->get(route('prophetic-words.audio', $word))->assertForbidden();
    }

    public function test_leader_without_prophecy_access_cannot_upload_or_listen(): void
    {
        $leader = $this->userWithRole(Role::Leader);
        $member = Profile::factory()->create();
        $word = PropheticWord::create(['profile_id' => $member->id, 'title' => 'Private', 'audio_path' => 'prophetic-words/x.mp3']);
        $group = LifeGroup::create(['name' => 'LG', 'category' => LifeGroupCategory::Mixed, 'leader_profile_id' => $leader->profile->id, 'status' => 'active']);
        app(LifeGroupService::class)->addMember($group, $member);

        $this->actingAs($leader)
            ->post(route('admin.members.prophetic-words.store', $member), ['title' => 'Nope', 'audio' => $this->mp3()])
            ->assertForbidden();
        $this->actingAs($leader)->get(route('prophetic-words.audio', $word))->assertForbidden();
    }

    public function test_program_completion_status_is_required_before_issuing(): void
    {
        $profile = Profile::factory()->create();
        $progress = $this->journey->startProgram($profile, $this->program('leadership-113'));

        $this->assertSame(ProgressStatus::InProgress, $progress->status);
        $this->assertSame(0, Certificate::count());
    }
}
