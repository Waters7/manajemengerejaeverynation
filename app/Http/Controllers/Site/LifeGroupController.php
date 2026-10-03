<?php

namespace App\Http\Controllers\Site;

use App\Enums\LifeGroupCategory;
use App\Enums\Weekday;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\JoinLifeGroupRequest;
use App\Models\LifeGroup;
use App\Services\LifeGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LifeGroupController extends Controller
{
    public function index(Request $request): View
    {
        $groups = LifeGroup::publiclyListed()
            ->with('leader')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('day'), fn ($q) => $q->where('meeting_day', $request->string('day')))
            ->when($request->filled('area'), fn ($q) => $q->where('area', 'like', '%'.$request->string('area').'%'))
            ->orderByDesc('accepting_members')
            ->orderBy('name')
            ->get();

        return view('site.lifegroups.index', [
            'groups' => $groups,
            'categories' => LifeGroupCategory::options(),
            'days' => Weekday::options(),
            'areas' => LifeGroup::publiclyListed()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
        ]);
    }

    public function show(LifeGroup $lifeGroup): View
    {
        abort_unless($lifeGroup->is_public && $lifeGroup->status === 'active', 404);

        return view('site.lifegroups.show', [
            'group' => $lifeGroup->load('leader', 'coLeader', 'campus'),
            'others' => LifeGroup::publiclyListed()->with('leader')->whereKeyNot($lifeGroup->id)
                ->where('category', $lifeGroup->category)->limit(3)->get(),
        ]);
    }

    public function join(JoinLifeGroupRequest $request, LifeGroup $lifeGroup, LifeGroupService $service): RedirectResponse
    {
        abort_unless($lifeGroup->is_public && $lifeGroup->accepting_members, 404);

        $service->submitJoinRequest($lifeGroup, $request->validated(), $request->user());

        return redirect()->route('lifegroups.show', $lifeGroup->slug)
            ->with('joined', true)
            ->with('status', 'Terima kasih! Leader LifeGroup akan segera menghubungimu via WhatsApp.');
    }
}
