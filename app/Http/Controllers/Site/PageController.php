<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\DiscipleshipStage;
use App\Models\Event;
use App\Models\LifeGroup;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('site.about', ['page' => Page::published()->where('slug', 'about')->first()]);
    }

    public function discipleship(): View
    {
        return view('site.discipleship', [
            'stages' => DiscipleshipStage::ordered()->with(['activePrograms' => fn ($q) => $q->withCount('chapters')])->get(),
            'page' => Page::published()->where('slug', 'discipleship')->first(),
        ]);
    }

    public function campus(): View
    {
        return view('site.campus', [
            'campuses' => Campus::where('is_active', true)->orderBy('name')->get(),
            'lifeGroups' => LifeGroup::publiclyListed()->with('leader')->whereNotNull('campus_id')->limit(6)->get(),
            'events' => Event::published()->upcoming()->with('category')->whereNotNull('campus_id')->limit(3)->get(),
        ]);
    }

    public function show(Page $page): View
    {
        abort_unless($page->isLive(), 404);

        return view('site.page', ['page' => $page]);
    }
}
