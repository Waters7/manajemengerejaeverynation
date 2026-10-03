<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\PrayerStatus;
use App\Enums\PrayerVisibility;
use App\Http\Controllers\Controller;
use App\Models\PrayerRequest;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CARE → Prayer Requests. Visibility (Pastor only / LifeGroup leader / Prayer Team) is enforced by AccessScope.
 */
class PrayerRequestController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $prayers = $this->scope->prayerRequests(PrayerRequest::query(), $request->user())
            ->with(['lifeGroup', 'assignee', 'profile'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', '!=', PrayerStatus::Answered->value))
            ->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->string('visibility')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.prayer-requests.index', [
            'prayers' => $prayers,
            'statuses' => PrayerStatus::options(),
            'visibilities' => PrayerVisibility::options(),
        ]);
    }

    public function show(Request $request, PrayerRequest $prayerRequest): View
    {
        $this->authorizePrayer($request, $prayerRequest);

        return view('admin.prayer-requests.show', [
            'prayer' => $prayerRequest->load(['lifeGroup', 'assignee', 'profile']),
            'statuses' => PrayerStatus::options(),
            'visibilities' => PrayerVisibility::options(),
            'team' => User::permission('prayer.view')->where('account_status', AccountStatus::Active->value)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, PrayerRequest $prayerRequest): RedirectResponse
    {
        $this->authorizePrayer($request, $prayerRequest);

        $data = $request->validate([
            'status' => ['required', Rule::enum(PrayerStatus::class)],
            'visibility' => ['sometimes', Rule::enum(PrayerVisibility::class)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'praise_report' => ['nullable', 'string', 'max:3000'],
        ]);

        // Only pastors may widen who can read a request.
        if (! $this->scope->isChurchWide($request->user())) {
            unset($data['visibility']);
        }
        if ($data['status'] === PrayerStatus::Answered->value && ! $prayerRequest->answered_at) {
            $data['answered_at'] = now();
        }

        $prayerRequest->update($data);

        return back()->with('status', 'Prayer request updated.');
    }

    private function authorizePrayer(Request $request, PrayerRequest $prayer): void
    {
        abort_unless($this->scope->prayerRequests(PrayerRequest::whereKey($prayer->id), $request->user())->exists(), 404);
    }
}
