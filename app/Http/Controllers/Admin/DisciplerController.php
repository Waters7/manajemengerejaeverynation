<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscipleshipMeetingRequest;
use App\Models\DisciplerRelationship;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\JourneyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * DISCIPLESHIP → Disciplers and the discipleship family tree.
 */
class DisciplerController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $visible = $this->scope->profiles(Profile::query(), $user)->select('profiles.id');

        $disciplers = Profile::query()
            ->whereHas('discipleRelationships', fn ($q) => $q->active()->whereIn('disciple_profile_id', $visible))
            ->withCount(['discipleRelationships as disciples_count' => fn ($q) => $q->active()])
            ->with(['currentStage', 'activeLifeGroups', 'discipleRelationships' => fn ($q) => $q->active()->with(['disciple.currentStage', 'disciple.currentProgram', 'latestMeeting'])])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->value()))
            ->orderByDesc('disciples_count')
            ->paginate(20)
            ->withQueryString();

        return view('admin.disciplers.index', ['disciplers' => $disciplers]);
    }

    public function tree(Request $request, JourneyService $journey): View
    {
        $user = $request->user();

        if ($request->filled('root')) {
            $root = Profile::findOrFail($request->integer('root'));
            abort_unless($this->scope->canSeeProfile($user, $root), 403);
            $roots = collect([$root]);
        } elseif ($this->scope->isChurchWide($user)) {
            // Top of each family: disciplers who are not themselves being discipled.
            $roots = Profile::whereHas('discipleRelationships', fn ($q) => $q->active())
                ->whereDoesntHave('disciplerRelationships', fn ($q) => $q->active())
                ->orderBy('full_name')->get();
        } else {
            $roots = collect([$user->ensureProfile()]);
        }

        return view('admin.disciplers.tree', [
            'trees' => $roots->map(fn (Profile $root) => $journey->tree($root)),
            'people' => $this->scope->profiles(Profile::whereHas('discipleRelationships', fn ($q) => $q->active()), $user)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function store(Request $request, JourneyService $journey): RedirectResponse
    {
        $data = $request->validate([
            'disciple_profile_id' => ['required', Rule::exists('profiles', 'id')],
            'discipler_profile_id' => ['required', 'different:disciple_profile_id', Rule::exists('profiles', 'id')],
            'started_at' => ['nullable', 'date'],
        ]);

        $disciple = Profile::findOrFail($data['disciple_profile_id']);
        $this->authorize('disciple', $disciple);
        $discipler = Profile::findOrFail($data['discipler_profile_id']);

        // A person's own disciple (or their disciples) cannot become their discipler.
        abort_if(in_array($discipler->id, $this->scope->downline($disciple->id), true), 422, 'This would create a loop in the discipleship tree.');

        $journey->assignDiscipler($disciple, $discipler, $data['started_at'] ?? null);

        return back()->with('status', "{$discipler->displayName()} is now discipling {$disciple->displayName()}.");
    }

    public function end(DisciplerRelationship $relationship, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $relationship->disciple);
        $journey->endRelationship($relationship);

        return back()->with('status', 'Discipleship relationship ended.');
    }

    public function meeting(DiscipleshipMeetingRequest $request, DisciplerRelationship $relationship, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $relationship->disciple);
        $journey->recordMeeting($relationship, $request->validated());

        return back()->with('status', 'Meeting recorded.');
    }
}
