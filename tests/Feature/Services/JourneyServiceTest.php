<?php

namespace Tests\Feature\Services;

use App\Enums\BaptismStatus;
use App\Enums\ProgressStatus;
use App\Models\DiscipleshipProgram;
use App\Models\Profile;
use App\Services\JourneyService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyServiceTest extends TestCase
{
    use RefreshDatabase;

    private JourneyService $journey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferenceDataSeeder::class);
        $this->journey = app(JourneyService::class);
    }

    private function program(string $slug): DiscipleshipProgram
    {
        return DiscipleshipProgram::where('slug', $slug)->with('chapters')->firstOrFail();
    }

    public function test_starting_one_2_one_places_the_person_in_engage(): void
    {
        $profile = Profile::factory()->create();

        $this->journey->startProgram($profile, $this->program('one-2-one'));

        $profile->refresh();
        $this->assertSame('ENGAGE', $profile->currentStage->name);
        $this->assertSame('One 2 One', $profile->currentProgram->name);
    }

    public function test_completing_the_last_chapter_completes_the_program_and_moves_to_next_stage(): void
    {
        $profile = Profile::factory()->create();
        $one2one = $this->program('one-2-one');
        $progress = $this->journey->startProgram($profile, $one2one);

        foreach ($one2one->chapters as $chapter) {
            $this->journey->updateChapter($progress, $chapter, ProgressStatus::Completed);
        }

        $this->assertSame(ProgressStatus::Completed, $progress->fresh()->status);
        $profile->refresh();
        $this->assertSame('ESTABLISH', $profile->currentStage->name);
        $this->assertSame('Preparing for Victory', $profile->currentProgram->name);
        $this->assertTrue($profile->timeline()->where('title', 'Completed One 2 One')->exists());
    }

    public function test_partial_book_progress_counts_completed_chapters(): void
    {
        $profile = Profile::factory()->create();
        $purple = $this->program('purple-book');
        $progress = $this->journey->startProgram($profile, $purple);

        foreach ($purple->chapters->take(7) as $chapter) {
            $this->journey->updateChapter($progress, $chapter, ProgressStatus::Completed);
        }

        $progress->refresh()->load('program.chapters', 'chapterProgress');
        $this->assertSame(7, $progress->completedUnits());
        $this->assertSame(12, $progress->program->totalUnits());
        $this->assertSame(ProgressStatus::InProgress, $progress->status);
    }

    public function test_unticking_a_chapter_reopens_a_completed_program(): void
    {
        $profile = Profile::factory()->create();
        $one2one = $this->program('one-2-one');
        $progress = $this->journey->startProgram($profile, $one2one);
        foreach ($one2one->chapters as $chapter) {
            $this->journey->updateChapter($progress, $chapter, ProgressStatus::Completed);
        }

        $this->journey->updateChapter($progress->fresh(), $one2one->chapters->last(), ProgressStatus::NotStarted);

        $this->assertSame(ProgressStatus::InProgress, $progress->fresh()->status);
        $this->assertSame('ENGAGE', $profile->fresh()->currentStage->name);
    }

    public function test_assigning_a_new_discipler_ends_the_previous_relationship(): void
    {
        [$disciple, $first, $second] = Profile::factory()->count(3)->create();

        $this->journey->assignDiscipler($disciple, $first);
        $this->journey->assignDiscipler($disciple, $second);

        $this->assertSame($second->id, $disciple->activeDiscipler()->first()->discipler_profile_id);
        $this->assertSame(1, $disciple->disciplerRelationships()->where('status', 'ended')->count());
    }

    public function test_recording_a_baptism_adds_a_timeline_milestone_once(): void
    {
        $profile = Profile::factory()->create();

        $this->journey->recordBaptism($profile, ['baptism_status' => 'baptized', 'baptism_date' => '2026-05-10', 'baptism_place' => 'Every Nation Bekasi']);
        $this->journey->recordBaptism($profile->fresh(), ['baptism_status' => 'baptized', 'baptism_date' => '2026-05-10', 'baptism_place' => 'Every Nation Bekasi']);

        $profile->refresh();
        $this->assertTrue($profile->isBaptized());
        $this->assertSame('2026-05-10', $profile->baptism_date->toDateString());
        $this->assertSame(1, $profile->timeline()->where('type', 'baptism')->count());
    }

    public function test_resetting_to_not_yet_clears_date_and_place(): void
    {
        $profile = Profile::factory()->create();
        $this->journey->recordBaptism($profile, ['baptism_status' => 'scheduled', 'baptism_date' => now()->addWeek()->toDateString(), 'baptism_place' => 'ENB']);

        $this->journey->recordBaptism($profile->fresh(), ['baptism_status' => 'not_yet']);

        $profile->refresh();
        $this->assertSame(BaptismStatus::NotYet, $profile->baptism_status);
        $this->assertNull($profile->baptism_date);
        $this->assertNull($profile->baptism_place);
    }

    public function test_tree_contains_multiple_generations(): void
    {
        [$leader, $disciple, $grandDisciple] = Profile::factory()->count(3)->create();
        $this->journey->assignDiscipler($disciple, $leader);
        $this->journey->assignDiscipler($grandDisciple, $disciple);

        $tree = $this->journey->tree($leader);

        $this->assertSame($disciple->id, $tree['children'][0]['profile']->id);
        $this->assertSame($grandDisciple->id, $tree['children'][0]['children'][0]['profile']->id);
    }
}
