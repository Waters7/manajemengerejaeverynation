<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Devotional;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DevotionalController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.devotionals.index', [
            'devotionals' => Devotional::query()
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
                ->latest('devotional_date')->paginate(25)->withQueryString(),
            'statuses' => ContentStatus::options(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.devotionals.form', [
            'devotional' => new Devotional(['devotional_date' => today(), 'status' => ContentStatus::Draft, 'author' => $request->user()->name]),
            'statuses' => ContentStatus::options(),
        ]);
    }

    public function store(Request $request, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        $devotional = Devotional::create($this->validated($request, $media) + ['created_by' => $request->user()->id]);
        $this->logPublish($devotional, $audit);

        return redirect()->route('admin.devotionals.index')->with('status', 'Devotional saved.');
    }

    public function edit(Devotional $devotional): View
    {
        return view('admin.devotionals.form', ['devotional' => $devotional, 'statuses' => ContentStatus::options()]);
    }

    public function update(Request $request, Devotional $devotional, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        $wasLive = $devotional->isLive();
        $devotional->update($this->validated($request, $media, $devotional));
        if (! $wasLive) {
            $this->logPublish($devotional, $audit);
        }

        return redirect()->route('admin.devotionals.index')->with('status', 'Devotional updated.');
    }

    public function destroy(Devotional $devotional): RedirectResponse
    {
        $devotional->delete();

        return back()->with('status', 'Devotional deleted.');
    }

    private function logPublish(Devotional $devotional, AuditLogger $audit): void
    {
        if ($devotional->isLive()) {
            $audit->log(AuditAction::Publish, $devotional, "Published devotional “{$devotional->title}”");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, MediaService $media, ?Devotional $devotional = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'verse' => ['nullable', 'string', 'max:1000'],
            'bible_reference' => ['nullable', 'string', 'max:120'],
            'opening' => ['nullable', 'string', 'max:2000'],
            'body' => ['required', 'string', 'max:20000'],
            'reflection' => ['nullable', 'string', 'max:3000'],
            'application' => ['nullable', 'string', 'max:3000'],
            'prayer' => ['nullable', 'string', 'max:3000'],
            'author' => ['nullable', 'string', 'max:120'],
            'devotional_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['body'] = HtmlSanitizer::clean($data['body']);
        $data['cover_path'] = $media->replace($devotional?->cover_path, $request->file('cover'), 'devotionals');

        return collect($data)->except('cover')->all();
    }
}
