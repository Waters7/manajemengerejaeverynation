<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Devotional;
use App\Models\DiscipleshipStage;
use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\LifeGroup;
use App\Models\Sermon;
use App\Services\Settings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Settings $settings): View
    {
        return view('site.home', [
            'events' => Event::published()->upcoming()->with('category')->limit(3)->get(),
            'stages' => DiscipleshipStage::ordered()->with('activePrograms')->get(),
            'devotional' => Devotional::published()->latest('devotional_date')->first(),
            'sermon' => Sermon::published()->with('series')->latest('preached_on')->first(),
            'lifeGroups' => LifeGroup::publiclyListed()->with('leader')->where('accepting_members', true)->inRandomOrder()->limit(3)->get(),
            'photos' => GalleryImage::whereHas('gallery', fn ($q) => $q->published())->latest()->limit(6)->get(),
            'testimonies' => $settings->testimonies(),
        ]);
    }
}
