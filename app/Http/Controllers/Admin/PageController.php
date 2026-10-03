<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CMS pages. The slugs "about" and "discipleship" replace the default text of those pages.
 */
class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::latest('updated_at')->paginate(30)]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['status' => ContentStatus::Draft]), 'statuses' => ContentStatus::options()]);
    }

    public function store(Request $request, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        $page = Page::create($this->validated($request, $media) + ['created_by' => $request->user()->id]);
        if ($page->isLive()) {
            $audit->log(AuditAction::Publish, $page, "Published page “{$page->title}”");
        }

        return redirect()->route('admin.pages.index')->with('status', 'Page saved.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page, 'statuses' => ContentStatus::options()]);
    }

    public function update(Request $request, Page $page, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        $wasLive = $page->isLive();
        $page->update($this->validated($request, $media, $page));
        if (! $wasLive && $page->isLive()) {
            $audit->log(AuditAction::Publish, $page, "Published page “{$page->title}”");
        }

        return redirect()->route('admin.pages.index')->with('status', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return back()->with('status', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, MediaService $media, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'alpha_dash', 'max:190', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:50000'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]);
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? null);
        $data['cover_path'] = $media->replace($page?->cover_path, $request->file('cover'), 'pages');

        return collect($data)->except('cover')->all();
    }
}
