<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Ministry;
use App\Models\MinistryMember;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\VolunteerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * MINISTRY → Volunteers across the ministries the user can see.
 */
class VolunteerController extends Controller
{
    public function index(Request $request, AccessScope $scope): View
    {
        $ministries = $scope->ministries(Ministry::query(), $request->user())->orderBy('name')->pluck('name', 'id');

        $volunteers = MinistryMember::with(['profile', 'ministry', 'role'])
            ->whereIn('ministry_id', $ministries->keys())
            ->when($request->filled('ministry'), fn ($q) => $q->where('ministry_id', $request->integer('ministry')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('profile', fn ($p) => $p->search($request->string('q')->value())))
            ->latest('joined_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.volunteers.index', [
            'volunteers' => $volunteers,
            'ministries' => $ministries,
            'statuses' => VolunteerStatus::options(),
        ]);
    }

    public function store(Request $request, Ministry $ministry, VolunteerService $service): RedirectResponse
    {
        $this->authorize('update', $ministry);
        $data = $request->validate(['profile_id' => ['required', Rule::exists('profiles', 'id')]]);

        $service->addToMinistry(Profile::findOrFail($data['profile_id']), $ministry, VolunteerStatus::Orientation);

        return back()->with('status', 'Volunteer added (orientation).');
    }

    public function update(Request $request, MinistryMember $member, VolunteerService $service): RedirectResponse
    {
        $this->authorize('update', $member->ministry);
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(VolunteerStatus::class)],
            'ministry_role_id' => ['sometimes', 'nullable', Rule::exists('ministry_roles', 'id')->where('ministry_id', $member->ministry_id)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (($data['status'] ?? null) === VolunteerStatus::Active->value && $member->status !== VolunteerStatus::Active) {
            $service->addToMinistry($member->profile, $member->ministry, VolunteerStatus::Active);
            unset($data['status']);
        }
        $member->update($data);

        return back()->with('status', 'Volunteer updated.');
    }

    public function destroy(MinistryMember $member): RedirectResponse
    {
        $this->authorize('update', $member->ministry);
        $member->update(['status' => VolunteerStatus::Inactive]);

        return back()->with('status', 'Volunteer marked inactive.');
    }
}
