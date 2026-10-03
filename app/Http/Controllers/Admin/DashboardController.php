<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\VolunteerApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\InvolvementRequest;
use App\Models\LifeGroup;
use App\Models\Ministry;
use App\Models\Profile;
use App\Models\ServingSchedule;
use App\Services\AccessScope;
use App\Services\CareRadar;
use App\Services\DashboardMetrics;
use App\Services\JourneyService;
use App\Services\LifeGroupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardMetrics $metrics,
        CareRadar $radar,
        JourneyService $journey,
        AccessScope $scope,
        LifeGroupService $lifeGroups,
    ): View {
        $user = $request->user();
        $churchWide = $scope->isChurchWide($user);

        $ledGroups = LifeGroup::with('leader')->whereIn('id', $scope->ledLifeGroupIds($user))->get()
            ->map(fn (LifeGroup $group) => ['group' => $group, 'summary' => $lifeGroups->summary($group)]);

        $ministries = $user->can('ministries.manage')
            ? $scope->ministries(Ministry::query(), $user)->withCount(['activeMembers', 'applications as waiting_count' => fn ($q) => $q->whereNotIn('status', [VolunteerApplicationStatus::Accepted->value, VolunteerApplicationStatus::Declined->value])])->get()
            : collect();

        return view('admin.dashboard', [
            'user' => $user,
            'churchWide' => $churchWide,
            'metrics' => $metrics->forUser($user),
            'funnel' => $journey->funnel(fn ($q) => $scope->profiles($q, $user)),
            'care' => $radar->sections($user),
            'recent' => $user->can('involvement.view')
                ? $scope->involvementRequests(InvolvementRequest::query()->open()->with('interests'), $user)->latest()->limit(6)->get()
                : collect(),
            'classes' => $user->can('classes.view')
                ? $scope->classBatches(ClassBatch::upcoming()->with('program')->withCount('activeParticipants'), $user)->orderBy('start_date')->limit(5)->get()
                : collect(),
            'ledGroups' => $ledGroups,
            'ministries' => $ministries,
            'serving' => $ministries->isNotEmpty()
                ? ServingSchedule::with(['profile', 'ministry', 'role'])->whereIn('ministry_id', $ministries->pluck('id'))->whereBetween('serve_date', [today(), today()->addDays(14)])->orderBy('serve_date')->limit(8)->get()
                : collect(),
            'isCampus' => $user->hasRole(Role::CampusMinistry->value),
            'campusStudents' => $user->hasRole(Role::CampusMinistry->value)
                ? Profile::whereIn('campus_id', $scope->campusIds($user))->count()
                : 0,
        ]);
    }
}
