<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * COMMUNICATION → Announcements shown on member and ministry dashboards.
 */
class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', ['announcements' => Announcement::with('author')->latest()->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.announcements.form', $this->formData(new Announcement(['status' => ContentStatus::Published, 'audience' => AnnouncementAudience::Everyone])));
    }

    public function store(Request $request): RedirectResponse
    {
        Announcement::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement saved.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', $this->formData($announcement));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('status', 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'is_pinned' => ['boolean'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Announcement $announcement): array
    {
        return ['announcement' => $announcement, 'statuses' => ContentStatus::options(), 'audiences' => AnnouncementAudience::options()];
    }
}
