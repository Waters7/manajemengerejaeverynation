<?php

namespace App\Http\Controllers\Site;

use App\Enums\Availability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ServeRequest;
use App\Models\Ministry;
use App\Services\VolunteerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServeController extends Controller
{
    public function create(): View
    {
        return view('site.get-involved.serve', [
            'ministries' => Ministry::active()->where('accepting_volunteers', true)->get(),
            'availability' => Availability::options(),
        ]);
    }

    public function store(ServeRequest $request, VolunteerService $volunteers): RedirectResponse
    {
        $data = $request->safe()->except(['ministries', 'website']);
        $volunteers->submit($data, array_map('intval', $request->validated('ministries')), $request->user());

        return redirect()->route('get-involved.thanks')->with('nickname', strtok($data['name'], ' '));
    }
}
