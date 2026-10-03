<?php

namespace App\Http\Controllers\Site;

use App\Enums\PrayerStatus;
use App\Enums\PrayerVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PrayerSubmissionRequest;
use App\Models\PrayerRequest;
use App\Notifications\TeamAlert;
use App\Services\TeamNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrayerController extends Controller
{
    public function create(): View
    {
        return view('site.prayer', ['visibilities' => [
            PrayerVisibility::PastorOnly->value => 'Pastor saja',
            PrayerVisibility::LifegroupLeader->value => 'Pastor & leader LifeGroup saya',
            PrayerVisibility::PrayerTeam->value => 'Pastor & Prayer Team',
        ]]);
    }

    public function store(PrayerSubmissionRequest $request, TeamNotifier $notifier): RedirectResponse
    {
        $user = $request->user();
        $profile = $user?->profile;

        $prayer = PrayerRequest::create([
            'profile_id' => $profile?->id,
            'user_id' => $user?->id,
            'life_group_id' => $profile?->activeLifeGroups()->value('life_groups.id'),
            'name' => $request->validated('name') ?? $user?->name,
            'contact' => $request->validated('contact') ?? $user?->whatsapp,
            'request' => $request->validated('request'),
            'visibility' => $request->validated('visibility'),
            'is_anonymous' => $request->boolean('is_anonymous'),
            'status' => PrayerStatus::New,
            'source' => $user ? 'member' : 'public',
        ]);

        $notifier->toPermission('pastoral.view', new TeamAlert('prayer', 'New prayer request', 'A new prayer request was submitted.', route('admin.prayer-requests.show', $prayer)));

        return redirect()->route('prayer.create')->with('prayed', true)->with('status', 'Terima kasih, kami akan mendoakan kamu.');
    }
}
