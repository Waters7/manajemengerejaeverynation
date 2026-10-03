<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\JourneyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DISCIPLESHIP → Journey: where each person is in the 4E journey.
 */
class JourneyController extends Controller
{
    public function index(Request $request, AccessScope $scope, JourneyService $journey): View
    {
        $user = $request->user();
        $quiet = $request->boolean('quiet');

        $progress = MemberProgramProgress::query()
            ->with(['profile.activeLifeGroups', 'program.stage', 'program.chapters', 'chapterProgress', 'discipler'])
            ->whereHas('profile', fn ($q) => $scope->profiles($q, $user))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('profile', fn ($p) => $p->search($request->string('q')->value())))
            ->when($request->filled('stage'), fn ($q) => $q->whereHas('program', fn ($p) => $p->where('discipleship_stage_id', $request->integer('stage'))))
            ->when($request->filled('program'), fn ($q) => $q->where('discipleship_program_id', $request->integer('program')))
            ->when($request->filled('discipler'), fn ($q) => $q->where('discipler_profile_id', $request->integer('discipler')))
            ->when($request->input('status', 'in_progress') !== 'all', fn ($q) => $q->where('status', $request->input('status', 'in_progress')))
            ->when($quiet, fn ($q) => $q->where(fn ($w) => $w->whereNull('last_activity_at')->orWhere('last_activity_at', '<', now()->subDays(30))))
            ->orderByRaw('next_follow_up_at is null')
            ->orderBy('next_follow_up_at')
            ->latest('last_activity_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.journey.index', [
            'progress' => $progress,
            'funnel' => $journey->funnel(fn ($q) => $scope->profiles($q, $user)),
            'stages' => DiscipleshipStage::ordered()->pluck('name', 'id'),
            'programs' => DiscipleshipProgram::active()->orderBy('sequence')->pluck('name', 'id'),
            'statuses' => ProgressStatus::options() + ['all' => 'All'],
            'disciplers' => Profile::whereIn('id', MemberProgramProgress::whereNotNull('discipler_profile_id')->select('discipler_profile_id'))->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }
}
