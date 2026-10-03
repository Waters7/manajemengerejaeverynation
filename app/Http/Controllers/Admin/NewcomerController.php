<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\MemberStatus;
use App\Enums\NewcomerJourney;
use App\Http\Controllers\Controller;
use App\Models\Newcomer;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Newcomer journey: FIRST VISIT → CONNECT CARD → CONTACTED → CONNECTED → LIFEGROUP → ONE 2 ONE → DISCIPLESHIP.
 */
class NewcomerController extends Controller
{
    public function index(Request $request, AccessScope $scope): View
    {
        $user = $request->user();
        $base = Newcomer::query()->whereHas('profile', fn ($q) => $scope->profiles($q, $user));

        $newcomers = (clone $base)
            ->with(['profile.activeLifeGroups', 'profile.activeDiscipler.discipler', 'profile.involvementRequests.interests', 'assignee'])
            ->when($request->filled('journey'), fn ($q) => $q->where('journey_status', $request->string('journey')))
            ->when($request->filled('assigned'), fn ($q) => $request->input('assigned') === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $request->integer('assigned')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('profile', fn ($p) => $p->search($request->string('q')->value())))
            ->when(! $request->boolean('all'), fn ($q) => $q->whereHas('profile', fn ($p) => $p->whereIn('member_status', [MemberStatus::Visitor->value, MemberStatus::Newcomer->value, MemberStatus::Connected->value])))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.newcomers.index', [
            'newcomers' => $newcomers,
            'journeys' => NewcomerJourney::options(),
            'counts' => (clone $base)->selectRaw('journey_status, count(*) as total')->groupBy('journey_status')->pluck('total', 'journey_status'),
            'team' => User::permission('newcomers.manage')->where('account_status', AccountStatus::Active->value)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, Newcomer $newcomer, AccessScope $scope): RedirectResponse
    {
        abort_unless($scope->canSeeProfile($request->user(), $newcomer->profile), 403);

        $data = $request->validate([
            'journey_status' => ['sometimes', Rule::enum(NewcomerJourney::class)],
            'assigned_to' => ['sometimes', 'nullable', Rule::exists('users', 'id')],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (($data['journey_status'] ?? null) === NewcomerJourney::Contacted->value) {
            $data['contacted_at'] = $newcomer->contacted_at ?? now();
        }
        if (($data['journey_status'] ?? null) === NewcomerJourney::Connected->value) {
            $data['connected_at'] = $newcomer->connected_at ?? now();
        }

        $newcomer->update($data);

        return back()->with('status', 'Newcomer updated.');
    }
}
