<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LifeGroup;
use App\Models\Profile;
use App\Services\AccessScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, AccessScope $scope): View
    {
        $term = trim((string) $request->string('q'));
        $user = $request->user();

        return view('admin.search', [
            'term' => $term,
            'people' => $term !== '' && $user->can('members.view')
                ? $scope->profiles(Profile::query()->search($term), $user)->with('activeLifeGroups')->limit(20)->get()
                : collect(),
            'groups' => $term !== '' && $user->can('lifegroups.view')
                ? $scope->lifeGroups(LifeGroup::where('name', 'like', "%{$term}%"), $user)->limit(10)->get()
                : collect(),
            'events' => $term !== '' && $user->can('events.manage')
                ? $scope->events(Event::where('title', 'like', "%{$term}%"), $user)->latest('starts_at')->limit(10)->get()
                : collect(),
        ]);
    }
}
