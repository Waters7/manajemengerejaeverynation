<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BirthdayService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BirthdayController extends Controller
{
    public function __invoke(Request $request, BirthdayService $birthdays): View
    {
        $range = array_key_exists($request->string('range')->value(), BirthdayService::RANGES) ? $request->string('range')->value() : 'month';

        return view('admin.birthdays', [
            'range' => $range,
            'ranges' => BirthdayService::RANGES,
            'rows' => $birthdays->upcoming($request->user(), $range),
        ]);
    }
}
