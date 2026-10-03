<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadershipStage;
use App\Http\Controllers\Controller;
use App\Models\LeadershipCandidate;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\LeadershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Leadership pipeline: Potential → Training → Ready → Approved → Active Leader.
 * Pastors/Admins make the final decision; nothing here is automatic.
 */
class LeadershipController extends Controller
{
    public function __construct(
        private AccessScope $scope,
        private LeadershipService $leadership,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $candidates = LeadershipCandidate::with(['profile.activeLifeGroups', 'profile.currentStage', 'recommender', 'decider'])
            ->whereHas('profile', fn ($q) => $this->scope->profiles($q, $user))
            ->get()
            ->groupBy(fn (LeadershipCandidate $c) => $c->stage->value);

        return view('admin.leadership.index', [
            'stages' => LeadershipStage::cases(),
            'candidates' => $candidates,
            'snapshots' => $candidates->flatten()->mapWithKeys(fn (LeadershipCandidate $c) => [$c->id => $this->leadership->snapshot($c->profile)]),
            'people' => $this->scope->profiles(Profile::members(), $user)->whereDoesntHave('leadershipCandidate')->orderBy('full_name')->pluck('full_name', 'id'),
            'canApprove' => $user->can('leadership.approve'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'profile_id' => ['required', Rule::exists('profiles', 'id')],
            'recommendation' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = Profile::findOrFail($data['profile_id']);
        abort_unless($this->scope->canSeeProfile($request->user(), $profile), 403);

        $this->leadership->nominate($profile, $data['recommendation'] ?? null);

        return back()->with('status', "{$profile->displayName()} added to the leadership pipeline.");
    }

    public function update(Request $request, LeadershipCandidate $candidate): RedirectResponse
    {
        abort_unless($this->scope->canSeeProfile($request->user(), $candidate->profile), 403);
        $data = $request->validate([
            'stage' => ['required', Rule::enum(LeadershipStage::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->leadership->moveTo($candidate, LeadershipStage::from($data['stage']), $request->user(), $data['notes'] ?? null);

        return back()->with('status', "{$candidate->profile->displayName()} moved to ".LeadershipStage::from($data['stage'])->label().'.');
    }
}
