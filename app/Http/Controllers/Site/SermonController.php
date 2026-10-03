<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Sermon;
use App\Models\SermonSeries;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SermonController extends Controller
{
    public function index(Request $request): View
    {
        $sermons = Sermon::published()
            ->with('series')
            ->when($request->filled('series'), fn ($q) => $q->whereHas('series', fn ($s) => $s->where('slug', $request->string('series'))))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', '%'.$request->string('q').'%')->orWhere('speaker', 'like', '%'.$request->string('q').'%')->orWhere('bible_text', 'like', '%'.$request->string('q').'%')))
            ->latest('preached_on')
            ->paginate(9)
            ->withQueryString();

        return view('site.sermons.index', [
            'sermons' => $sermons,
            'series' => SermonSeries::whereHas('sermons', fn ($q) => $q->published())->orderBy('name')->get(),
        ]);
    }

    public function show(Sermon $sermon): View
    {
        abort_unless($sermon->isLive(), 404);

        return view('site.sermons.show', [
            'sermon' => $sermon->load('series', 'points'),
            'more' => Sermon::published()->whereKeyNot($sermon->id)
                ->when($sermon->sermon_series_id, fn ($q) => $q->where('sermon_series_id', $sermon->sermon_series_id))
                ->latest('preached_on')->limit(3)->get(),
        ]);
    }
}
