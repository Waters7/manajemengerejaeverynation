<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Services\EventRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->ensureProfile();

        $registrations = EventRegistration::with('event')
            ->where(fn ($q) => $q->where('profile_id', $profile->id)->orWhere('user_id', $request->user()->id))
            ->latest()
            ->get()
            ->partition(fn (EventRegistration $r) => $r->event && $r->event->starts_at->gte(now()->startOfDay()));

        return view('member.events', ['upcoming' => $registrations[0], 'past' => $registrations[1]]);
    }

    public function cancel(Request $request, EventRegistration $registration, EventRegistrationService $service): RedirectResponse
    {
        $profileId = $request->user()->profile?->id;
        abort_unless($registration->user_id === $request->user()->id || ($profileId && $registration->profile_id === $profileId), 403);

        $service->cancel($registration);

        return back()->with('status', 'Pendaftaran dibatalkan.');
    }
}
