<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ServingSchedule;
use App\Models\VolunteerApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profile = $request->user()->ensureProfile();

        return view('member.serving', [
            'memberships' => $profile->ministryMemberships()->with(['ministry.coordinator', 'role'])->get(),
            'applications' => VolunteerApplication::with('ministries')->where('profile_id', $profile->id)->latest()->get(),
            'schedule' => ServingSchedule::with(['ministry', 'role'])->where('profile_id', $profile->id)
                ->whereDate('serve_date', '>=', today())->orderBy('serve_date')->limit(10)->get(),
        ]);
    }
}
