<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\LifeGroup;
use App\Models\LifeGroupMeeting;
use App\Services\AccessScope;
use App\Services\LifeGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * LifeGroup meetings with Present / Absent / Excused attendance.
 */
class MeetingController extends Controller
{
    public function index(Request $request, AccessScope $scope): View
    {
        $groups = $scope->lifeGroups(LifeGroup::active(), $request->user())->orderBy('name')->pluck('name', 'id');

        $meetings = LifeGroupMeeting::with('lifeGroup')
            ->withCount(['attendances', 'attendances as present_count' => fn ($q) => $q->where('status', AttendanceStatus::Present->value)])
            ->whereIn('life_group_id', $request->filled('lifegroup') ? [$request->integer('lifegroup')] : $groups->keys())
            ->whereIn('life_group_id', $groups->keys())
            ->when($request->filled('from'), fn ($q) => $q->whereDate('meeting_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('meeting_date', '<=', $request->date('to')))
            ->latest('meeting_date')
            ->paginate(30)
            ->withQueryString();

        return view('admin.meetings.index', ['meetings' => $meetings, 'groups' => $groups]);
    }

    public function create(LifeGroup $lifeGroup): View
    {
        $this->authorize('update', $lifeGroup);

        return view('admin.meetings.form', $this->formData($lifeGroup, new LifeGroupMeeting(['meeting_date' => today(), 'location' => $lifeGroup->location])));
    }

    public function store(Request $request, LifeGroup $lifeGroup, LifeGroupService $service): RedirectResponse
    {
        $this->authorize('update', $lifeGroup);
        [$data, $attendance] = $this->validated($request);

        $service->recordMeeting($lifeGroup, $data, $attendance);

        return redirect()->route('admin.lifegroups.show', $lifeGroup)->with('status', 'Meeting and attendance saved.');
    }

    public function edit(LifeGroupMeeting $meeting): View
    {
        $this->authorize('update', $meeting->lifeGroup);

        return view('admin.meetings.form', $this->formData($meeting->lifeGroup, $meeting->load('attendances')));
    }

    public function update(Request $request, LifeGroupMeeting $meeting, LifeGroupService $service): RedirectResponse
    {
        $this->authorize('update', $meeting->lifeGroup);
        [$data, $attendance] = $this->validated($request);

        $service->recordMeeting($meeting->lifeGroup, $data, $attendance, $meeting);

        return redirect()->route('admin.lifegroups.show', $meeting->lifeGroup)->with('status', 'Meeting updated.');
    }

    public function destroy(LifeGroupMeeting $meeting): RedirectResponse
    {
        $this->authorize('update', $meeting->lifeGroup);
        $group = $meeting->lifeGroup;
        $meeting->delete();

        return redirect()->route('admin.lifegroups.show', $group)->with('status', 'Meeting deleted.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'meeting_date' => ['required', 'date', 'before_or_equal:today'],
            'topic' => ['nullable', 'string', 'max:190'],
            'location' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'visitor_count' => ['nullable', 'integer', 'min:0', 'max:200'],
            'attendance' => ['array'],
            'attendance.*' => [Rule::enum(AttendanceStatus::class)],
        ]);

        return [collect($data)->except('attendance')->all(), $data['attendance'] ?? []];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(LifeGroup $group, LifeGroupMeeting $meeting): array
    {
        return [
            'group' => $group,
            'meeting' => $meeting,
            'members' => $group->memberships()->with('profile')->where('status', 'active')->get()->sortBy('profile.full_name'),
            'current' => $meeting->exists ? $meeting->attendances->pluck('status', 'profile_id')->map->value : collect(),
            'statuses' => AttendanceStatus::options(),
        ];
    }
}
