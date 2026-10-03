<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Ministry;
use App\Models\ServingSchedule;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * MINISTRY → Serving Schedule, shown per week.
 */
class ServingScheduleController extends Controller
{
    public function index(Request $request, AccessScope $scope): View
    {
        $ministries = $scope->ministries(Ministry::query(), $request->user())->with(['roles', 'activeMembers.profile'])->orderBy('name')->get();
        $ministry = $request->filled('ministry') ? $ministries->firstWhere('id', $request->integer('ministry')) : $ministries->first();
        $weekStart = Carbon::parse($request->input('week', today()->startOfWeek()->toDateString()))->startOfWeek();

        return view('admin.serving.index', [
            'ministries' => $ministries,
            'ministry' => $ministry,
            'weekStart' => $weekStart,
            'slots' => $ministry
                ? ServingSchedule::with(['profile', 'role'])->where('ministry_id', $ministry->id)
                    ->whereBetween('serve_date', [$weekStart, $weekStart->copy()->addWeeks(4)->endOfWeek()])
                    ->orderBy('serve_date')->get()->groupBy(fn ($s) => $s->serve_date->toDateString())
                : collect(),
            'statuses' => ServingSchedule::STATUSES,
            'canManage' => $ministry && $request->user()->can('update', $ministry),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ministry_id' => ['required', Rule::exists('ministries', 'id')],
            'profile_id' => ['required', Rule::exists('ministry_members', 'profile_id')->where('ministry_id', $request->integer('ministry_id'))->where('status', VolunteerStatus::Active->value)],
            'ministry_role_id' => ['nullable', Rule::exists('ministry_roles', 'id')->where('ministry_id', $request->integer('ministry_id'))],
            'serve_date' => ['required', 'date'],
            'service_label' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $this->authorize('update', Ministry::findOrFail($data['ministry_id']));

        ServingSchedule::create($data + ['status' => 'scheduled']);

        return back()->with('status', 'Added to the serving schedule.');
    }

    public function update(Request $request, ServingSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule->ministry);
        $schedule->update($request->validate(['status' => ['required', Rule::in(array_keys(ServingSchedule::STATUSES))]]));

        return back()->with('status', 'Schedule updated.');
    }

    public function destroy(ServingSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule->ministry);
        $schedule->delete();

        return back()->with('status', 'Removed from schedule.');
    }
}
