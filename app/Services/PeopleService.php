<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\NewcomerJourney;
use App\Models\Newcomer;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Person records: find-or-create from public forms, newcomer journey and member activation.
 */
class PeopleService
{
    public function __construct(private TimelineRecorder $timeline) {}

    /**
     * Match an existing person by WhatsApp (normalised) or e-mail, otherwise create one.
     * Existing data is never overwritten by blank values; only empty fields are filled.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function findOrCreate(array $attributes): Profile
    {
        $attributes['whatsapp'] = WhatsApp::normalize($attributes['whatsapp'] ?? null);
        $attributes = array_filter($attributes, fn ($value) => $value !== null && $value !== '');

        $profile = null;
        if (! empty($attributes['whatsapp'])) {
            $profile = Profile::where('whatsapp', $attributes['whatsapp'])->first();
        }
        if (! $profile && ! empty($attributes['email'])) {
            $profile = Profile::where('email', $attributes['email'])->first();
        }

        if ($profile) {
            $missing = collect($attributes)->filter(fn ($value, $key) => blank($profile->getAttribute($key)))->all();
            if ($missing !== []) {
                $profile->fill($missing)->save();
            }

            return $profile;
        }

        $profile = Profile::create($attributes + [
            'member_status' => MemberStatus::Newcomer,
            'first_visit_date' => today(),
            'created_by' => Auth::id(),
        ]);

        $this->timeline->record($profile, 'first_visit', 'First connected with Every Nation Bekasi', null, null, $profile->first_visit_date);

        return $profile;
    }

    /** Ensure there is a newcomer journey record and move it forward (never backwards). */
    public function advanceNewcomer(Profile $profile, NewcomerJourney $stage, array $extra = []): Newcomer
    {
        $newcomer = $profile->newcomer ?? $profile->newcomer()->create([
            'journey_status' => NewcomerJourney::FirstVisit,
            'first_visit_date' => $profile->first_visit_date ?? today(),
            'source' => $profile->source,
        ]);

        $order = array_flip(NewcomerJourney::values());
        if ($order[$stage->value] > $order[$newcomer->journey_status->value]) {
            $newcomer->journey_status = $stage;
        }

        if ($stage === NewcomerJourney::Contacted && ! $newcomer->contacted_at) {
            $newcomer->contacted_at = now();
        }
        if ($stage === NewcomerJourney::Connected && ! $newcomer->connected_at) {
            $newcomer->connected_at = now();
        }

        $newcomer->fill($extra)->save();
        $profile->setRelation('newcomer', $newcomer);

        return $newcomer;
    }

    /** Newcomer → active member. Membership is a pastoral decision, so it is always explicit. */
    public function activateMember(Profile $profile): Profile
    {
        return DB::transaction(function () use ($profile) {
            if ($profile->member_status !== MemberStatus::Member) {
                $profile->update([
                    'member_status' => MemberStatus::Member,
                    'join_date' => $profile->join_date ?? today(),
                ]);
                $this->timeline->record($profile, 'member', 'Became an active member');
            }

            return $profile;
        });
    }
}
