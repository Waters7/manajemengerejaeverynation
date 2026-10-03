<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnnouncementAudience;
use App\Enums\ParticipantStatus;
use App\Enums\RegistrationStatus;
use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Devotional;
use App\Models\Event;
use App\Models\Sermon;
use App\Services\JourneyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, JourneyService $journey): View
    {
        $user = $request->user();
        $profile = $user->ensureProfile()->load(['currentStage', 'currentProgram', 'activeLifeGroups.leader']);

        $currentProgress = $profile->current_program_id
            ? $profile->programProgress()->with(['program.chapters', 'chapterProgress', 'discipler'])->where('discipleship_program_id', $profile->current_program_id)->first()
            : null;

        return view('member.dashboard', [
            'user' => $user,
            'profile' => $profile,
            'currentProgress' => $currentProgress,
            'discipler' => $profile->activeDiscipler()->with('discipler')->first()?->discipler,
            'classes' => $profile->classParticipations()->with('batch.program')
                ->whereIn('status', [ParticipantStatus::Registered->value, ParticipantStatus::Confirmed->value, ParticipantStatus::InProgress->value])->get(),
            'registrations' => $profile->eventRegistrations()->with('event')
                ->where('status', '!=', RegistrationStatus::Cancelled->value)
                ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', now()->startOfDay()))->get(),
            'serving' => $profile->ministryMemberships()->with('ministry')->where('status', '!=', VolunteerStatus::Inactive->value)->get(),
            'announcements' => Announcement::current()->whereIn('audience', [AnnouncementAudience::Everyone->value, AnnouncementAudience::Members->value])->limit(3)->get(),
            'devotional' => Devotional::published()->latest('devotional_date')->first(),
            'sermon' => Sermon::published()->latest('preached_on')->first(),
            'events' => Event::published()->upcoming()->limit(3)->get(),
            'stages' => $journey->journey($profile),
        ]);
    }
}
