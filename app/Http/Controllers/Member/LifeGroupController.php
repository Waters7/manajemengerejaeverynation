<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LifeGroupJoinRequest;
use App\Models\LifeGroupMeeting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LifeGroupController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profile = $request->user()->ensureProfile();

        $groups = $profile->activeLifeGroups()
            ->with(['leader', 'coLeader', 'activeMembers' => fn ($q) => $q->orderBy('full_name')])
            ->get();

        $meetings = $groups->isEmpty() ? collect() : LifeGroupMeeting::whereIn('life_group_id', $groups->pluck('id'))
            ->with(['attendances' => fn ($q) => $q->where('profile_id', $profile->id)])
            ->latest('meeting_date')->limit(8)->get();

        return view('member.lifegroup', [
            'groups' => $groups,
            'meetings' => $meetings,
            'requests' => $profile->id ? LifeGroupJoinRequest::with('lifeGroup')->where('profile_id', $profile->id)->latest()->limit(5)->get() : collect(),
        ]);
    }
}
