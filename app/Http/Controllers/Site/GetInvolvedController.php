<?php

namespace App\Http\Controllers\Site;

use App\Enums\Availability;
use App\Enums\DiscoverySource;
use App\Enums\Gender;
use App\Enums\InvolvementType;
use App\Enums\LifeStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\GetInvolvedRequest;
use App\Models\Campus;
use App\Models\Ministry;
use App\Services\InvolvementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GetInvolvedController extends Controller
{
    public function create(InvolvementService $service): View
    {
        return view('site.get-involved.create', [
            'interests' => $service->interests(),
            'ministries' => Ministry::active()->where('accepting_volunteers', true)->get(),
            'campuses' => Campus::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'genders' => Gender::options(),
            'lifeStages' => LifeStage::options(),
            'sources' => DiscoverySource::options(),
            'availability' => Availability::options(),
        ]);
    }

    public function store(GetInvolvedRequest $request, InvolvementService $service): RedirectResponse
    {
        $involvement = $service->submit(
            $request->safe()->except(['consent', 'website']),
            InvolvementType::GetInvolved,
            $request->user(),
            $request->ip(),
        );

        return redirect()->route('get-involved.thanks')->with('nickname', $involvement->displayName());
    }

    public function thanks(): View
    {
        return view('site.get-involved.thanks', ['nickname' => session('nickname')]);
    }
}
