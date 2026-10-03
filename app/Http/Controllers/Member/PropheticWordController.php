<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropheticWordController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profile = $request->user()->ensureProfile();
        $request->user()->unreadNotifications()->where('data->category', 'prophetic_word')->update(['read_at' => now()]);

        return view('member.prophetic-words', ['words' => $profile->propheticWords()->get()]);
    }
}
