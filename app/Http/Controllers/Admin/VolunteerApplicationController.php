<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Enums\VolunteerApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Ministry;
use App\Models\VolunteerApplication;
use App\Services\AccessScope;
use App\Services\VolunteerService;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Serve With Us applications: Review → Contact → Interview → Orientation → Accept / Decline.
 */
class VolunteerApplicationController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $applications = $this->scope->volunteerApplications(VolunteerApplication::query(), $request->user())
            ->with(['ministries', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->awaiting())
            ->when($request->filled('ministry'), fn ($q) => $q->whereHas('ministries', fn ($m) => $m->where('ministries.id', $request->integer('ministry'))))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.volunteer-applications.index', [
            'applications' => $applications,
            'statuses' => VolunteerApplicationStatus::options(),
            'ministries' => $this->scope->ministries(Ministry::query(), $request->user())->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function show(Request $request, VolunteerApplication $application, WhatsApp $whatsApp): View
    {
        $this->authorizeApplication($request, $application);

        return view('admin.volunteer-applications.show', [
            'application' => $application->load(['ministries', 'profile', 'reviewer', 'contactNotes.author', 'involvementRequest']),
            'statuses' => VolunteerApplicationStatus::options(),
            'waLink' => $whatsApp->templateLink($application->whatsapp, 'wa_template_volunteer', [
                'nickname' => strtok($application->name, ' '),
                'ministry' => $application->ministries->pluck('name')->implode(' / '),
                'sender' => $request->user()->displayName(),
            ]),
        ]);
    }

    public function update(Request $request, VolunteerApplication $application, VolunteerService $volunteers): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $data = $request->validate([
            'status' => ['required', Rule::enum(VolunteerApplicationStatus::class)],
            'interview_at' => ['nullable', 'date'],
        ]);

        $volunteers->transition($application, VolunteerApplicationStatus::from($data['status']), $data['interview_at'] ?? null);

        return back()->with('status', 'Application moved to '.VolunteerApplicationStatus::from($data['status'])->label().'.');
    }

    public function note(Request $request, VolunteerApplication $application): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000'], 'type' => ['required', Rule::enum(ContactType::class)]]);

        $application->contactNotes()->create($data + ['profile_id' => $application->profile_id, 'user_id' => $request->user()->id]);

        return back()->with('status', 'Note added.');
    }

    private function authorizeApplication(Request $request, VolunteerApplication $application): void
    {
        abort_unless($this->scope->volunteerApplications(VolunteerApplication::whereKey($application->id), $request->user())->exists(), 403);
    }
}
