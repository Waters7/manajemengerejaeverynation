<?php

namespace App\Http\Controllers\Site;

use App\Enums\InvolvementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ConnectCardRequest;
use App\Services\InvolvementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Digital Connect Card — opened from a QR code during Sunday Service.
 */
class ConnectController extends Controller
{
    public function create(InvolvementService $service): View
    {
        return view('site.connect', ['interests' => $service->interests(connectCardOnly: true)]);
    }

    public function store(ConnectCardRequest $request, InvolvementService $service): RedirectResponse
    {
        $data = $request->safe()->except('website');
        $data['source'] = 'sunday_service';

        $involvement = $service->submit($data, InvolvementType::ConnectCard, $request->user(), $request->ip());

        return redirect()->route('get-involved.thanks')->with('nickname', $involvement->displayName());
    }
}
