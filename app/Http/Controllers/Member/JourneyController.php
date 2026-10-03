<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\JourneyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JourneyController extends Controller
{
    public function __invoke(Request $request, JourneyService $journey): View
    {
        $profile = $request->user()->ensureProfile()->load('currentStage', 'currentProgram');

        return view('member.journey', [
            'profile' => $profile,
            'stages' => $journey->journey($profile),
            'discipler' => $profile->activeDiscipler()->with('discipler')->first(),
            'timeline' => $profile->timeline()->limit(30)->get(),
        ]);
    }
}
