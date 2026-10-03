<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BatchStatus;
use App\Enums\ContentStatus;
use App\Enums\InvolvementStatus;
use App\Enums\InvolvementType;
use App\Enums\JoinRequestStatus;
use App\Enums\LeadershipStage;
use App\Enums\LifeGroupCategory;
use App\Enums\LifeGroupRole;
use App\Enums\MemberStatus;
use App\Enums\NewcomerJourney;
use App\Enums\ParticipantStatus;
use App\Enums\PrayerStatus;
use App\Enums\PrayerVisibility;
use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerStatus;
use App\Enums\Weekday;
use App\Models\Campus;
use App\Models\ClassBatch;
use App\Models\Devotional;
use App\Models\DiscipleshipProgram;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\InvolvementInterest;
use App\Models\InvolvementRequest;
use App\Models\LeadershipCandidate;
use App\Models\LifeGroup;
use App\Models\LifeGroupJoinRequest;
use App\Models\Ministry;
use App\Models\PrayerRequest;
use App\Models\Profile;
use App\Models\Sermon;
use App\Models\SermonSeries;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Services\ClassService;
use App\Services\JourneyService;
use App\Services\LifeGroupService;
use App\Services\VolunteerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Demo data so every dashboard has something meaningful to show.
 *
 * Demo accounts use DEMO_PASSWORD from .env. Locally it falls back to "password";
 * in production DEMO_PASSWORD is required so no account is created with a known password.
 */
class DemoSeeder extends Seeder
{
    private string $password = 'password';

    public function run(JourneyService $journey, LifeGroupService $lifeGroups, ClassService $classes, VolunteerService $volunteers): void
    {
        if (Profile::count() > 5) {
            $this->command?->info('Demo data already present — skipped.');

            return;
        }

        $configured = env('DEMO_PASSWORD');
        if (app()->isProduction() && (blank($configured) || strlen($configured) < 10)) {
            $this->command?->warn('Demo data skipped: set DEMO_PASSWORD (min. 10 characters) in .env to seed demo accounts in production.');

            return;
        }
        $this->password = $configured ?: 'password';

        $campuses = collect(['President University', 'Universitas Bhayangkara Jakarta Raya', 'Universitas Gunadarma Bekasi'])
            ->map(fn ($name, $i) => Campus::create(['name' => $name, 'short_name' => ['PresUniv', 'Ubhara', 'Gunadarma'][$i], 'is_active' => true]));

        $pastor = $this->account('Pastor Daniel Hartono', 'Ps. Daniel', 'pastor@everynationbekasi.test', Role::Pastor);
        $campusMinister = $this->account('Grace Natalia', 'Grace', 'campus@everynationbekasi.test', Role::CampusMinistry, $campuses[0]);
        $campusMinister->campuses()->sync([$campuses[0]->id, $campuses[1]->id]);
        $leaderA = $this->account('Michael Tan', 'Michael', 'leader@everynationbekasi.test', Role::Leader);
        $leaderB = $this->account('Kevin Wijaya', 'Kevin', 'leader2@everynationbekasi.test', Role::Leader);
        $coordinator = $this->account('Stefanie Lim', 'Stef', 'coordinator@everynationbekasi.test', Role::MinistryCoordinator);
        $welcome = $this->account('Yohana Sitompul', 'Yohana', 'welcome@everynationbekasi.test', Role::WelcomeTeam);
        $member = $this->account('Sarah Lim', 'Sarah', 'member@everynationbekasi.test', Role::User);

        Ministry::whereIn('slug', ['worship', 'music', 'multimedia', 'hospitality'])->update(['coordinator_id' => $coordinator->id]);

        // LifeGroups
        $groups = collect([
            ['Bekasi Barat Young Professionals', LifeGroupCategory::YoungProfessionals, 'Bekasi Barat', Weekday::Friday, '19:30', $leaderA, null],
            ['Summarecon Couples', LifeGroupCategory::Couples, 'Bekasi Utara', Weekday::Saturday, '18:30', $leaderB, null],
            ['PresUniv Campus LG', LifeGroupCategory::Campus, 'Cikarang', Weekday::Wednesday, '18:00', $campusMinister, $campuses[0]],
            ['Jatiasih Women', LifeGroupCategory::Women, 'Jatiasih', Weekday::Thursday, '19:00', $pastor, null],
        ])->map(fn ($g) => LifeGroup::create([
            'name' => $g[0], 'category' => $g[1], 'area' => $g[2], 'meeting_day' => $g[3], 'meeting_time' => $g[4],
            'leader_profile_id' => $g[5]->profile->id, 'campus_id' => $g[6]?->id, 'location' => $g[2],
            'description' => 'Sebuah komunitas kecil untuk bertumbuh bersama dalam Firman, doa, dan persahabatan. Semua orang welcome!',
            'accepting_members' => true, 'is_public' => true, 'whatsapp_invite_url' => 'https://chat.whatsapp.com/'.Str::random(22), 'status' => 'active',
        ]));

        foreach ($groups as $i => $group) {
            $lifeGroups->addMember($group, $group->leader, LifeGroupRole::Leader);
        }

        // People
        $people = Profile::factory()->count(36)->create()->each(function (Profile $p, int $i) use ($groups, $campuses, $lifeGroups) {
            if ($i % 9 === 0) {
                $p->update(['campus_id' => $campuses[$i % 3]->id, 'school_name' => $campuses[$i % 3]->name, 'life_stage' => 'mahasiswa']);
            }
            $lifeGroups->addMember($groups[$i % $groups->count()], $p, $i % 11 === 0 ? LifeGroupRole::Visitor : LifeGroupRole::Member);
        });
        $lifeGroups->addMember($groups[0], $member->profile);
        $people->push($member->profile);

        // Discipleship: disciplers, progress across the 4E journey
        $programs = $journey->orderedPrograms()->values();
        $leaderProfiles = collect([$leaderA, $leaderB, $campusMinister, $pastor])->map->profile;

        foreach ($people as $i => $person) {
            $discipler = $i < 12 ? $leaderProfiles[$i % 4] : $people[$i % 12];
            if ($discipler->id !== $person->id) {
                $relationship = $journey->assignDiscipler($person, $discipler, now()->subMonths(rand(1, 18))->toDateString());
                $relationship->meetings()->create(['met_on' => now()->subDays(rand(2, 40)), 'topic' => 'Coffee & catch up', 'notes' => 'Encouraged in prayer and Bible reading.']);
            }

            $reach = $i % count($programs);
            foreach ($programs->take($reach + 1) as $index => $program) {
                $progress = $journey->startProgram($person, $program, null, now()->subMonths(18 - $index)->toDateString());
                if ($index < $reach) {
                    $progress->load('program.chapters');
                    foreach ($progress->program->chapters as $chapter) {
                        $journey->updateChapter($progress, $chapter, ProgressStatus::Completed, now()->subMonths(12 - $index)->toDateString());
                    }
                    if ($progress->fresh()->status !== ProgressStatus::Completed) {
                        $journey->completeProgram($progress, now()->subMonths(max(1, 12 - $index))->toDateString());
                    }
                } else {
                    $progress->load('program.chapters');
                    foreach ($progress->program->chapters->take((int) floor($progress->program->chapters->count() * 0.6)) as $chapter) {
                        $journey->updateChapter($progress, $chapter, ProgressStatus::Completed, now()->subDays(rand(3, 50))->toDateString());
                    }
                    $progress->forceFill(['last_activity_at' => now()->subDays($i % 5 === 0 ? 40 : rand(1, 10))])->save();
                }
            }
        }

        // Water baptism: most people past One 2 One are baptized, a few are scheduled
        foreach ($people as $i => $person) {
            if ($i % count($programs) === 0) {
                continue;
            }
            $journey->recordBaptism($person, $i % 7 === 0
                ? ['baptism_status' => 'scheduled', 'baptism_date' => now()->next('Sunday')->addWeeks(2)->toDateString(), 'baptism_place' => 'Every Nation Bekasi']
                : ['baptism_status' => 'baptized', 'baptism_date' => now()->subMonths(rand(2, 30))->toDateString(), 'baptism_place' => $i % 5 ? 'Every Nation Bekasi' : 'Gereja sebelumnya']);
        }

        // Member Sarah: Establish → Purple Book 7/12, like the example in the brief
        foreach ($programs as $program) {
            $progress = $journey->startProgram($member->profile, $program, $leaderA->profile, now()->subMonths(8)->toDateString());
            $chapters = $program->chapters()->get();
            if ($program->slug === 'purple-book') {
                foreach ($chapters->take(7) as $chapter) {
                    $journey->updateChapter($progress, $chapter, ProgressStatus::Completed, now()->subDays(30 - $chapter->number * 3)->toDateString());
                }
                $progress->forceFill(['next_follow_up_at' => today()->addDays(4)])->save();
                break;
            }
            foreach ($chapters as $chapter) {
                $journey->updateChapter($progress, $chapter, ProgressStatus::Completed, now()->subMonths(6)->toDateString());
            }
            if ($progress->fresh()->status !== ProgressStatus::Completed) {
                $journey->completeProgram($progress, now()->subMonths(5)->toDateString());
            }
        }

        // Leadership pipeline
        foreach ($people->slice(0, 6)->values() as $i => $person) {
            LeadershipCandidate::create([
                'profile_id' => $person->id,
                'stage' => [LeadershipStage::Potential, LeadershipStage::Training, LeadershipStage::Ready, LeadershipStage::Potential, LeadershipStage::Training, LeadershipStage::Approved][$i],
                'recommended_by' => $leaderA->id,
                'recommendation' => 'Faithful, teachable and already caring for others in the LifeGroup.',
            ]);
        }

        // LifeGroup meetings & attendance
        foreach ($groups as $group) {
            foreach (range(1, 6) as $week) {
                $attendance = $group->memberships()->pluck('profile_id')
                    ->mapWithKeys(fn ($id) => [$id => collect([AttendanceStatus::Present->value, AttendanceStatus::Present->value, AttendanceStatus::Present->value, AttendanceStatus::Absent->value, AttendanceStatus::Excused->value])->random()])
                    ->all();
                $lifeGroups->recordMeeting($group, [
                    'meeting_date' => now()->subWeeks($week)->toDateString(),
                    'topic' => collect(['Faith that works', 'Prayer & fasting', 'Grace in community', 'Walking in the Spirit', 'Generosity', 'Identity in Christ'])[$week - 1],
                    'location' => $group->area,
                    'visitor_count' => rand(0, 3),
                ], $attendance);
            }
        }

        // Classes & Victory Weekend batches
        $pfv = DiscipleshipProgram::where('slug', 'preparing-for-victory')->first();
        $l113 = DiscipleshipProgram::where('slug', 'leadership-113')->first();
        $vw = DiscipleshipProgram::where('slug', 'victory-weekend')->first();
        foreach ([[$pfv, 'Batch Oktober 2026', BatchStatus::Ongoing, -14], [$l113, 'Batch Oktober 2026', BatchStatus::Planned, 10], [$vw, 'Victory Weekend November 2026', BatchStatus::Planned, 35]] as [$program, $name, $status, $offset]) {
            $batch = ClassBatch::create([
                'discipleship_program_id' => $program->id, 'name' => $name, 'start_date' => now()->addDays($offset), 'end_date' => now()->addDays($offset + 28),
                'facilitator_profile_id' => $pastor->profile->id, 'location' => 'Every Nation Bekasi — Main Hall', 'capacity' => 30,
                'registration_status' => 'open', 'status' => $status,
            ]);
            $classes->generateSessions($batch);
            foreach ($people->shuffle()->take(8) as $person) {
                $participant = $classes->enroll($batch, $person, false);
                if ($status === BatchStatus::Ongoing) {
                    $participant->update(['status' => ParticipantStatus::InProgress]);
                }
            }
            if ($status === BatchStatus::Ongoing) {
                foreach ($batch->sessions()->take(2)->get() as $session) {
                    $classes->recordAttendance($session, $batch->participants()->pluck('id')->mapWithKeys(fn ($id) => [$id => rand(0, 4) ? 'present' : 'absent'])->all());
                }
            }
        }

        // Ministries & volunteers
        foreach ($people->slice(10, 12)->values() as $i => $person) {
            $ministry = Ministry::whereIn('slug', ['worship', 'music', 'multimedia', 'hospitality', 'usher', 'kids-ministry'])->get()[$i % 6];
            $volunteers->addToMinistry($person, $ministry, VolunteerStatus::Active, ['Teamwork'], ['sunday']);
        }

        // Newcomers & Get Involved
        $interests = InvolvementInterest::all()->keyBy('action');
        $newcomers = Profile::factory()->newcomer()->count(8)->create();
        foreach ($newcomers as $i => $newcomer) {
            $newcomer->newcomer()->create([
                'journey_status' => [NewcomerJourney::ConnectCard, NewcomerJourney::Contacted, NewcomerJourney::ConnectCard, NewcomerJourney::Connected][$i % 4],
                'first_visit_date' => $newcomer->first_visit_date,
                'source' => ['friend', 'social_media', 'sunday_service', 'campus_ministry'][$i % 4],
                'assigned_to' => $i % 2 ? $welcome->id : null,
            ]);
            $request = InvolvementRequest::create([
                'profile_id' => $newcomer->id, 'type' => $i % 3 ? InvolvementType::GetInvolved : InvolvementType::ConnectCard,
                'full_name' => $newcomer->full_name, 'nickname' => $newcomer->nickname, 'gender' => $newcomer->gender, 'birth_date' => $newcomer->birth_date,
                'whatsapp' => $newcomer->whatsapp, 'email' => $newcomer->email, 'area' => $newcomer->area, 'occupation' => $newcomer->occupation,
                'life_stage' => $newcomer->life_stage, 'source' => ['friend', 'social_media', 'sunday_service', 'campus_ministry'][$i % 4],
                'status' => [InvolvementStatus::New, InvolvementStatus::Contacted, InvolvementStatus::New, InvolvementStatus::FollowUp][$i % 4],
                'assigned_to' => $i % 2 ? $welcome->id : null, 'due_date' => $i % 2 ? today()->addDays(3) : null,
                'created_at' => now()->subDays($i * 2),
            ]);
            $request->interests()->sync(collect(['lifegroup', 'one2one', 'volunteer', 'know_church', 'campus'])->shuffle()->take(2)->map(fn ($a) => $interests[$a]->id ?? null)->filter()->all());
            if ($request->interests()->where('action', 'volunteer')->exists()) {
                $request->ministries()->sync(Ministry::inRandomOrder()->limit(2)->pluck('id'));
            }
            $newcomer->timeline()->create(['type' => 'connect_card', 'title' => 'Filled a Connect Card', 'occurred_at' => $request->created_at]);
        }

        foreach ($newcomers->take(3) as $i => $newcomer) {
            LifeGroupJoinRequest::create([
                'life_group_id' => $groups[$i]->id, 'profile_id' => $newcomer->id, 'name' => $newcomer->full_name, 'whatsapp' => $newcomer->whatsapp,
                'area' => $newcomer->area, 'age' => $newcomer->age(), 'status' => [JoinRequestStatus::Pending, JoinRequestStatus::Contacted, JoinRequestStatus::Approved][$i],
                'created_at' => now()->subDays(4 - $i),
            ]);
        }

        foreach ($newcomers->slice(3, 2) as $newcomer) {
            $application = VolunteerApplication::create([
                'profile_id' => $newcomer->id, 'name' => $newcomer->full_name, 'whatsapp' => $newcomer->whatsapp, 'email' => $newcomer->email,
                'area' => $newcomer->area, 'church_connection' => 'Attending Sunday Service', 'skills' => ['Photography', 'Canva'],
                'experience' => 'Pernah melayani di tim multimedia gereja sebelumnya.', 'availability' => ['sunday'],
                'motivation' => 'Ingin memakai talenta untuk melayani Tuhan.', 'status' => VolunteerApplicationStatus::Submitted,
            ]);
            $application->ministries()->sync(Ministry::whereIn('slug', ['multimedia', 'photography'])->pluck('id'));
        }

        // Prayer requests
        foreach (['Mohon doa untuk kesembuhan mama saya.', 'Doakan pekerjaan baru saya.', 'Bersyukur untuk kelulusan!', 'Doakan keluarga saya agar dipulihkan.'] as $i => $text) {
            PrayerRequest::create([
                'profile_id' => $people[$i]->id, 'name' => $people[$i]->full_name, 'request' => $text, 'life_group_id' => $groups[$i % 4]->id,
                'visibility' => [PrayerVisibility::PastorOnly, PrayerVisibility::LifegroupLeader, PrayerVisibility::PrayerTeam, PrayerVisibility::LifegroupLeader][$i],
                'status' => [PrayerStatus::New, PrayerStatus::Praying, PrayerStatus::Answered, PrayerStatus::FollowUp][$i],
                'praise_report' => $i === 2 ? 'Puji Tuhan, sudah lulus dengan baik!' : null, 'source' => 'member',
            ]);
        }

        $this->content($pastor, $groups, $campuses);
    }

    private function account(string $name, string $nickname, string $email, Role $role, ?Campus $campus = null): User
    {
        $user = User::create([
            'name' => $name, 'nickname' => $nickname, 'email' => $email, 'whatsapp' => '628'.fake()->numerify('##########'),
            'password' => $this->password, 'account_status' => AccountStatus::Active, 'email_verified_at' => now(),
        ]);
        $user->assignRole($role->value);
        $user->profile()->create([
            'full_name' => $name, 'nickname' => $nickname, 'email' => $email, 'whatsapp' => $user->whatsapp,
            'gender' => fake()->randomElement(['male', 'female']), 'birth_date' => fake()->dateTimeBetween('-40 years', '-22 years'),
            'area' => 'Bekasi', 'member_status' => MemberStatus::Member, 'join_date' => now()->subYears(3), 'campus_id' => $campus?->id,
        ]);

        return $user->load('profile');
    }

    /**
     * @param  Collection<int, LifeGroup>  $groups
     * @param  Collection<int, Campus>  $campuses
     */
    private function content(User $pastor, Collection $groups, Collection $campuses): void
    {
        $categories = EventCategory::all()->keyBy('slug');
        $events = [
            ['Sunday Service', 'sunday-service', 3, 'Ibadah Minggu bersama seluruh keluarga Every Nation Bekasi.', null, false],
            ['Victory Weekend November', 'discipleship', 35, 'Akhir pekan untuk mengalami Tuhan secara pribadi. Wajib mengikuti Preparing for Victory.', 60, true],
            ['Campus Night: Purpose', 'campus', 12, 'Malam persekutuan mahasiswa — worship, sharing, dan makan bersama.', 80, true],
            ['Night of Prayer', 'prayer', 8, 'Bersama-sama mencari wajah Tuhan untuk kota Bekasi.', null, false],
            ['Leadership Summit 2026', 'training', 50, 'Hari pelatihan untuk semua leader dan calon leader.', 120, true],
        ];
        foreach ($events as [$title, $category, $days, $description, $capacity, $registration]) {
            Event::create([
                'event_category_id' => $categories[$category]->id ?? null, 'title' => $title, 'excerpt' => $description,
                'description' => "<p>{$description}</p><p>Ajak teman dan keluargamu!</p>", 'starts_at' => now()->addDays($days)->setTime(18, 30),
                'ends_at' => now()->addDays($days)->setTime(21, 0), 'location' => 'Every Nation Bekasi', 'capacity' => $capacity,
                'registration_enabled' => $registration, 'waiting_list_enabled' => true, 'contact_person' => 'Yohana',
                'campus_id' => $category === 'campus' ? $campuses[0]->id : null, 'status' => ContentStatus::Published, 'created_by' => $pastor->id,
            ]);
        }

        foreach (range(0, 5) as $i) {
            Devotional::create([
                'title' => ['Berakar di dalam Kasih', 'Hidup yang Menghormati Tuhan', 'Murid yang Memuridkan', 'Kekuatan dalam Komunitas', 'Iman di Tengah Badai', 'Kasih Karunia yang Cukup'][$i],
                'verse' => ['"Aku telah disalibkan dengan Kristus; namun aku hidup, tetapi bukan lagi aku sendiri yang hidup, melainkan Kristus yang hidup di dalam aku."', '"Karena itu pergilah, jadikanlah semua bangsa murid-Ku."', '"Hendaklah kamu berakar di dalam Dia dan dibangun di atas Dia."'][$i % 3],
                'bible_reference' => ['Galatia 2:20', 'Matius 28:19', 'Kolose 2:7'][$i % 3],
                'opening' => 'Setiap hari kita diundang untuk kembali kepada Tuhan dan menemukan hidup di dalam-Nya.',
                'body' => '<p>Pemuridan bukan program, melainkan perjalanan seumur hidup mengikut Yesus bersama orang lain. Ketika kita membuka hati kepada Firman-Nya, Roh Kudus membentuk karakter kita.</p><p>Hari ini, ambil waktu untuk diam di hadapan-Nya dan biarkan kasih-Nya meneguhkan langkahmu.</p>',
                'reflection' => 'Area mana dalam hidupmu yang Tuhan sedang ajak untuk bertumbuh?',
                'application' => 'Kirim pesan kepada satu orang di LifeGroup-mu dan doakan dia hari ini.',
                'prayer' => 'Tuhan Yesus, ajar aku mengikut-Mu dengan sepenuh hati. Amin.',
                'author' => 'Ps. Daniel Hartono', 'devotional_date' => today()->subDays($i), 'status' => ContentStatus::Published, 'created_by' => $pastor->id,
            ]);
        }

        $series = SermonSeries::create(['name' => 'Honor God, Make Disciples', 'description' => 'Seri khotbah tentang visi gereja kita.']);
        foreach (range(0, 3) as $i) {
            $sermon = Sermon::create([
                'sermon_series_id' => $series->id, 'title' => ['Honor God', 'Make Disciples', 'Every Nation', 'One by One'][$i],
                'speaker' => 'Ps. Daniel Hartono', 'preached_on' => today()->previous('Sunday')->subWeeks($i),
                'bible_text' => ['Matius 22:37-39', 'Matius 28:18-20', 'Wahyu 7:9', 'Lukas 15:4'][$i],
                'summary' => 'Kita dipanggil untuk menghormati Tuhan dalam segala hal dan menjadikan murid di mana pun kita berada.',
                'reflection' => 'Siapa satu orang yang bisa kamu bantu untuk mengenal Yesus lebih dalam?',
                'application' => 'Mulai One 2 One dengan seseorang bulan ini.',
                'status' => ContentStatus::Published, 'created_by' => $pastor->id,
            ]);
            $sermon->points()->createMany([
                ['sequence' => 1, 'title' => 'Tuhan layak menerima yang terbaik', 'body' => 'Menghormati Tuhan dimulai dari hati yang menyembah.'],
                ['sequence' => 2, 'title' => 'Pemuridan terjadi dalam relasi', 'body' => 'Yesus memuridkan dua belas orang dengan hidup bersama mereka.'],
                ['sequence' => 3, 'title' => 'Multiplikasi adalah tujuan', 'body' => 'Murid yang sehat akan memuridkan orang lain.'],
            ]);
        }
    }
}
