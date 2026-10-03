<?php

namespace Tests\Feature\Site;

use App\Enums\InterestAction;
use App\Enums\InvolvementStatus;
use App\Enums\NewcomerJourney;
use App\Enums\VolunteerApplicationStatus;
use App\Models\InvolvementInterest;
use App\Models\InvolvementRequest;
use App\Models\Ministry;
use App\Models\PrayerRequest;
use App\Models\Profile;
use App\Models\VolunteerApplication;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetInvolvedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Sarah Lim',
            'nickname' => 'Sarah',
            'whatsapp' => '0812-3456-7890',
            'email' => 'sarah@example.com',
            'area' => 'Bekasi Barat',
            'life_stage' => 'young_professional',
            'source' => 'friend',
            'interests' => [$this->interest(InterestAction::Lifegroup)->id, $this->interest(InterestAction::One2one)->id],
            'consent' => '1',
        ], $overrides);
    }

    private function interest(InterestAction $action): InvolvementInterest
    {
        return InvolvementInterest::where('action', $action->value)->firstOrFail();
    }

    public function test_renders_the_public_form_with_configured_interests(): void
    {
        $this->get(route('get-involved'))
            ->assertOk()
            ->assertSee("LET'S GET CONNECTED.")
            ->assertSee('Memulai One 2 One');
    }

    public function test_valid_submission_creates_request_person_and_newcomer_journey(): void
    {
        $response = $this->post(route('get-involved.store'), $this->payload());

        $response->assertRedirect(route('get-involved.thanks'));

        $request = InvolvementRequest::sole();
        $this->assertSame(InvolvementStatus::New, $request->status);
        $this->assertSame('6281234567890', $request->whatsapp);
        $this->assertCount(2, $request->interests);

        $profile = Profile::sole();
        $this->assertSame($profile->id, $request->profile_id);
        $this->assertSame(NewcomerJourney::ConnectCard, $profile->newcomer->journey_status);
    }

    public function test_submitting_twice_with_same_number_reuses_the_person_record(): void
    {
        $this->post(route('get-involved.store'), $this->payload());
        $this->post(route('get-involved.store'), $this->payload(['whatsapp' => '+62 812 3456 7890', 'full_name' => 'Sarah L.']));

        $this->assertSame(1, Profile::count());
        $this->assertSame(2, InvolvementRequest::count());
    }

    public function test_volunteer_interest_with_ministries_creates_a_volunteer_application(): void
    {
        $ministries = Ministry::whereIn('slug', ['worship', 'multimedia'])->pluck('id')->all();

        $this->post(route('get-involved.store'), $this->payload([
            'interests' => [$this->interest(InterestAction::Volunteer)->id],
            'ministries' => $ministries,
            'skills' => ['Guitar'],
            'availability' => ['sunday'],
            'motivation' => 'Ingin melayani',
        ]))->assertRedirect(route('get-involved.thanks'));

        $application = VolunteerApplication::sole();
        $this->assertSame(VolunteerApplicationStatus::Submitted, $application->status);
        $this->assertEqualsCanonicalizing($ministries, $application->ministries->pluck('id')->all());
    }

    public function test_prayer_interest_stores_a_pastor_only_prayer_request(): void
    {
        $this->post(route('get-involved.store'), $this->payload([
            'interests' => [$this->interest(InterestAction::Prayer)->id],
            'prayer_request' => 'Doakan keluarga saya',
        ]));

        $prayer = PrayerRequest::sole();
        $this->assertSame('pastor_only', $prayer->visibility->value);
        $this->assertSame('Doakan keluarga saya', $prayer->request);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $this->post(route('get-involved.store'), [])
            ->assertSessionHasErrors(['full_name', 'nickname', 'whatsapp', 'interests', 'consent']);

        $this->assertSame(0, InvolvementRequest::count());
    }

    public function test_invalid_whatsapp_number_shows_a_helpful_message(): void
    {
        $this->post(route('get-involved.store'), $this->payload(['whatsapp' => '12']))
            ->assertSessionHasErrors(['whatsapp' => 'Format nomor WhatsApp belum sesuai, contoh: 081234567890.']);
    }

    public function test_filled_honeypot_rejects_the_submission(): void
    {
        $this->post(route('get-involved.store'), $this->payload(['website' => 'http://spam.test']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, InvolvementRequest::count());
    }
}
