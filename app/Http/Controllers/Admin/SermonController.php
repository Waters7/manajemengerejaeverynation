<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Sermon;
use App\Models\SermonSeries;
use App\Services\AuditLogger;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SermonController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.sermons.index', [
            'sermons' => Sermon::with('series')
                ->when($request->filled('series'), fn ($q) => $q->where('sermon_series_id', $request->integer('series')))
                ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
                ->latest('preached_on')->paginate(25)->withQueryString(),
            'series' => SermonSeries::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('admin.sermons.form', $this->formData(new Sermon(['preached_on' => today()->previous('Sunday'), 'status' => ContentStatus::Draft])));
    }

    public function store(Request $request, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $media, $audit) {
            $sermon = Sermon::create($this->validated($request, $media) + ['created_by' => $request->user()->id]);
            $this->syncPoints($sermon, $request);
            if ($sermon->isLive()) {
                $audit->log(AuditAction::Publish, $sermon, "Published sermon “{$sermon->title}”");
            }
        });

        return redirect()->route('admin.sermons.index')->with('status', 'Sermon saved.');
    }

    public function edit(Sermon $sermon): View
    {
        return view('admin.sermons.form', $this->formData($sermon->load('points')));
    }

    public function update(Request $request, Sermon $sermon, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $sermon, $media, $audit) {
            $wasLive = $sermon->isLive();
            $sermon->update($this->validated($request, $media, $sermon));
            $this->syncPoints($sermon, $request);
            if (! $wasLive && $sermon->isLive()) {
                $audit->log(AuditAction::Publish, $sermon, "Published sermon “{$sermon->title}”");
            }
        });

        return redirect()->route('admin.sermons.index')->with('status', 'Sermon updated.');
    }

    public function destroy(Sermon $sermon): RedirectResponse
    {
        $sermon->delete();

        return back()->with('status', 'Sermon deleted.');
    }

    public function storeSeries(Request $request): RedirectResponse
    {
        SermonSeries::create($request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:sermon_series,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('status', 'Series created.');
    }

    private function syncPoints(Sermon $sermon, Request $request): void
    {
        $points = collect($request->input('points', []))
            ->filter(fn ($point) => filled($point['title'] ?? null))
            ->values();

        $sermon->points()->delete();
        foreach ($points as $i => $point) {
            $sermon->points()->create([
                'sequence' => $i + 1,
                'title' => mb_substr($point['title'], 0, 190),
                'body' => isset($point['body']) ? mb_substr($point['body'], 0, 2000) : null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, MediaService $media, ?Sermon $sermon = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'sermon_series_id' => ['nullable', Rule::exists('sermon_series', 'id')],
            'speaker' => ['nullable', 'string', 'max:120'],
            'preached_on' => ['required', 'date'],
            'bible_text' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'reflection' => ['nullable', 'string', 'max:3000'],
            'application' => ['nullable', 'string', 'max:3000'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'spotify_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'points' => ['array', 'max:20'],
            'points.*.title' => ['nullable', 'string', 'max:190'],
            'points.*.body' => ['nullable', 'string', 'max:2000'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]);
        $data['cover_path'] = $media->replace($sermon?->cover_path, $request->file('cover'), 'sermons');

        return collect($data)->except(['cover', 'points'])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Sermon $sermon): array
    {
        return [
            'sermon' => $sermon,
            'series' => SermonSeries::orderBy('name')->pluck('name', 'id'),
            'statuses' => ContentStatus::options(),
        ];
    }
}
