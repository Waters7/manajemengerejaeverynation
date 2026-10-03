<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Devotional;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DevotionalController extends Controller
{
    public function index(Request $request): View
    {
        $devotionals = Devotional::published()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', '%'.$request->string('q').'%')->orWhere('bible_reference', 'like', '%'.$request->string('q').'%')))
            ->latest('devotional_date')
            ->paginate(9)
            ->withQueryString();

        return view('site.devotionals.index', ['devotionals' => $devotionals]);
    }

    public function show(Devotional $devotional): View
    {
        abort_unless($devotional->isLive(), 404);

        return view('site.devotionals.show', [
            'devotional' => $devotional,
            'previous' => Devotional::published()->where('devotional_date', '<', $devotional->devotional_date)->latest('devotional_date')->first(),
            'next' => Devotional::published()->where('devotional_date', '>', $devotional->devotional_date)->oldest('devotional_date')->first(),
        ]);
    }
}
