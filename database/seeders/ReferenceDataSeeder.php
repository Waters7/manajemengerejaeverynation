<?php

namespace Database\Seeders;

use App\Enums\InterestAction;
use App\Enums\ProgramType;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\EventCategory;
use App\Models\InvolvementInterest;
use App\Models\Ministry;
use App\Models\PastoralCareCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Default, admin-editable reference data: 4E curriculum, interests, ministries and categories.
 * Chapter / lesson titles are placeholders — rename them in Discipleship → Curriculum.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->curriculum();
        $this->interests();
        $this->ministries();
        $this->categories();
    }

    private function curriculum(): void
    {
        $stages = [
            ['ENGAGE', 'Connect with God and with people.', '#0067B9', [
                ['One 2 One', ProgramType::Book, 7, 'lesson', 'Personal discipleship through the One 2 One booklet with a discipler.'],
            ]],
            ['ESTABLISH', 'Build a strong spiritual foundation.', '#0B7A75', [
                ['Preparing for Victory', ProgramType::ClassProgram, 4, null, 'Preparation class before attending Victory Weekend.'],
                ['Victory Weekend', ProgramType::Event, 1, null, 'A weekend encounter with God — a key discipleship milestone.', true],
                ['Purple Book', ProgramType::Book, 12, 'chapter', 'Biblical foundations, studied chapter by chapter with a discipler.'],
            ]],
            ['EQUIP', 'Be equipped to make disciples.', '#7C3AED', [
                ['Church Community', ProgramType::ClassProgram, 4, null, 'Understanding the church community and our part in it.'],
                ['Making Disciples 1', ProgramType::ClassProgram, 5, null, 'Learning to make disciples who make disciples.'],
                ['Making Disciples 2', ProgramType::ClassProgram, 5, null, 'Continuing the disciple-making training.'],
            ]],
            ['EMPOWER', 'Be empowered to lead and multiply.', '#D97706', [
                ['Leadership 113', ProgramType::Training, 3, null, 'Leadership training for emerging leaders.'],
                ['Leadership 215', ProgramType::Training, 3, null, 'Advanced leadership training.'],
            ]],
        ];

        $previous = null;
        foreach ($stages as $stageIndex => [$stageName, $tagline, $color, $programs]) {
            $stage = DiscipleshipStage::updateOrCreate(
                ['slug' => Str::slug($stageName)],
                ['name' => $stageName, 'tagline' => $tagline, 'color' => $color, 'sequence' => $stageIndex + 1, 'is_active' => true],
            );

            foreach ($programs as $programIndex => $definition) {
                [$name, $type, $units, $unitLabel, $description] = $definition;
                $isMilestone = $definition[5] ?? false;

                $program = DiscipleshipProgram::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'discipleship_stage_id' => $stage->id,
                        'name' => $name,
                        'description' => $description,
                        'type' => $type,
                        'sequence' => $programIndex + 1,
                        'prerequisite_id' => $previous?->id,
                        'is_required' => true,
                        'is_milestone' => $isMilestone,
                        'total_sessions' => $units,
                        'status' => 'active',
                    ],
                );

                if ($unitLabel && ! $program->chapters()->exists()) {
                    foreach (range(1, $units) as $n) {
                        $program->chapters()->create(['number' => $n, 'title' => Str::title($unitLabel).' '.$n, 'sequence' => $n]);
                    }
                }

                $previous = $program;
            }
        }
    }

    private function interests(): void
    {
        $interests = [
            ['Mengenal Every Nation Bekasi', InterestAction::KnowChurch, 'heart', true],
            ['Mengikuti Sunday Service', InterestAction::SundayService, 'sun', false],
            ['Bergabung dengan LifeGroup', InterestAction::Lifegroup, 'users', true],
            ['Memulai One 2 One', InterestAction::One2one, 'book', true],
            ['Mengikuti proses pemuridan', InterestAction::Discipleship, 'sprout', false],
            ['Mengikuti kelas discipleship', InterestAction::DiscipleshipClass, 'academic', false],
            ['Mengikuti Campus Ministry', InterestAction::Campus, 'campus', true],
            ['Menjadi volunteer', InterestAction::Volunteer, 'hand', true],
            ['Bergabung dalam pelayanan / ministry', InterestAction::Ministry, 'sparkles', false],
            ['Mengikuti event', InterestAction::Event, 'calendar', false],
            ['Mengajukan prayer request', InterestAction::Prayer, 'pray', true],
            ['Membutuhkan pastoral follow-up', InterestAction::Pastoral, 'chat', false],
            ['Ingin mengetahui lebih banyak tentang Jesus', InterestAction::KnowJesus, 'cross', false],
        ];

        foreach ($interests as $i => [$name, $action, $icon, $onConnectCard]) {
            InvolvementInterest::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'action' => $action, 'icon' => $icon, 'on_connect_card' => $onConnectCard, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }
    }

    private function ministries(): void
    {
        $ministries = ['Worship', 'Music', 'Creative', 'Multimedia', 'Photography', 'Videography', 'Social Media', 'Production',
            'Sound', 'Usher', 'Hospitality', 'Prayer', 'Kids Ministry', 'Campus Ministry', 'LifeGroup', 'Administration',
            'Event', 'Design', 'IT / Technology'];

        foreach ($ministries as $i => $name) {
            $ministry = Ministry::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'accepting_volunteers' => true, 'sort_order' => $i + 1],
            );
            if (! $ministry->roles()->exists()) {
                $ministry->roles()->createMany([['name' => 'Team Member'], ['name' => 'Team Lead']]);
            }
        }
    }

    private function categories(): void
    {
        $eventCategories = [
            ['Sunday Service', '#0067B9'], ['LifeGroup', '#0B7A75'], ['Discipleship', '#7C3AED'], ['Campus', '#DB2777'],
            ['Prayer', '#0891B2'], ['Training', '#D97706'], ['Special Event', '#111827'],
        ];
        foreach ($eventCategories as $i => [$name, $color]) {
            EventCategory::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'color' => $color, 'sort_order' => $i + 1]);
        }

        foreach (['Prayer', 'Family', 'Marriage', 'Counseling', 'Hospital', 'Bereavement', 'Other'] as $i => $name) {
            PastoralCareCategory::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true, 'sort_order' => $i + 1]);
        }
    }
}
