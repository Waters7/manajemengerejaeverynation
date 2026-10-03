<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ContactType;
use App\Enums\InterestAction;
use App\Enums\InvolvementStatus;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\InvolvementInterest;
use App\Models\InvolvementRequest;
use App\Models\LifeGroup;
use App\Models\Ministry;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\Exporter;
use App\Services\InvolvementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * COMMUNITY → Get Involved Requests.
 */
class InvolvementController extends Controller
{
    public function __construct(
        private AccessScope $scope,
        private InvolvementService $involvement,
    ) {}

    public function index(Request $request): View
    {
        $requests = $this->filtered($request)
            ->with(['interests', 'ministries', 'assignee', 'campus'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.involvement.index', [
            'requests' => $requests,
            'statuses' => InvolvementStatus::options(),
            'interests' => InvolvementInterest::active()->pluck('name', 'id'),
            'campuses' => Campus::orderBy('name')->pluck('name', 'id'),
            'counts' => $this->scope->involvementRequests(InvolvementRequest::query()->open(), $request->user())
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function export(Request $request, Exporter $exporter): StreamedResponse
    {
        $rows = $this->filtered($request)->with(['interests', 'ministries', 'assignee'])->latest()->get()
            ->map(fn (InvolvementRequest $r) => [
                $r->created_at->format('Y-m-d H:i'), $r->full_name, $r->nickname, $r->whatsapp, $r->email, $r->age(), $r->area,
                $r->interests->pluck('name')->implode(', '), $r->ministries->pluck('name')->implode(', '),
                $r->campus_name, $r->status->label(), $r->assignee?->name,
            ]);

        return $exporter->download('get-involved-'.now()->format('Ymd'), $request->input('format', 'xlsx'),
            ['Registered', 'Name', 'Nickname', 'WhatsApp', 'Email', 'Age', 'Area', 'Interests', 'Ministries', 'Campus', 'Status', 'Assigned to'], $rows);
    }

    public function show(Request $request, InvolvementRequest $involvementRequest): View
    {
        $this->authorize('view', $involvementRequest);
        $user = $request->user();

        $involvementRequest->load(['interests', 'ministries', 'assignee', 'campus', 'profile.activeLifeGroups', 'profile.activeDiscipler.discipler', 'notes.author', 'volunteerApplications']);

        return view('admin.involvement.show', [
            'item' => $involvementRequest,
            'statuses' => InvolvementStatus::options(),
            'team' => User::permission('followups.manage')->where('account_status', AccountStatus::Active->value)->orderBy('name')->get(),
            'lifeGroups' => $this->scope->lifeGroups(LifeGroup::active(), $user)->orderBy('name')->pluck('name', 'id'),
            'ministries' => Ministry::active()->pluck('name', 'id'),
            'disciplers' => $this->scope->profiles(Profile::members(), $user)->orderBy('full_name')->pluck('full_name', 'id'),
            'waLink' => $this->involvement->whatsappLink($involvementRequest, $user),
            'contactTypes' => ContactType::options(),
        ]);
    }

    public function assign(Request $request, InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        abort_unless($request->user()->can('involvement.manage'), 403);

        $data = $request->validate([
            'assigned_to' => ['required', Rule::exists('users', 'id')],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $assignee = User::findOrFail($data['assigned_to']);
        abort_unless($assignee->can('followups.manage') && $assignee->isActive(), 422, 'This person cannot take follow-up tasks.');

        $this->involvement->assign($involvementRequest->load('interests'), $assignee, $data['due_date'] ?? null);

        return back()->with('status', "Assigned to {$assignee->name}.");
    }

    public function status(Request $request, InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $data = $request->validate(['status' => ['required', Rule::enum(InvolvementStatus::class)]]);

        $this->involvement->updateStatus($involvementRequest, InvolvementStatus::from($data['status']));

        return back()->with('status', 'Status updated.');
    }

    public function note(Request $request, InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
            'type' => ['required', Rule::enum(ContactType::class)],
        ]);

        $this->involvement->addNote($involvementRequest, $data['body'], ContactType::from($data['type']));

        return back()->with('status', 'Note added.');
    }

    public function lifeGroup(Request $request, InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $data = $request->validate(['life_group_id' => ['required', Rule::exists('life_groups', 'id')]]);
        $group = LifeGroup::findOrFail($data['life_group_id']);
        abort_unless($this->scope->canManageLifeGroup($request->user(), $group) || $request->user()->can('involvement.manage'), 403);

        $this->involvement->assignLifeGroup($involvementRequest, $group);

        return back()->with('status', "Added to LifeGroup {$group->name}.");
    }

    public function ministry(Request $request, InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $data = $request->validate(['ministry_id' => ['required', Rule::exists('ministries', 'id')]]);
        $ministry = Ministry::findOrFail($data['ministry_id']);

        $this->involvement->assignMinistry($involvementRequest, $ministry);

        return back()->with('status', "Introduced to {$ministry->name} (orientation).");
    }

    public function one2one(Request $request, InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $data = $request->validate(['discipler_profile_id' => ['nullable', Rule::exists('profiles', 'id')]]);

        $this->involvement->startOne2One($involvementRequest, isset($data['discipler_profile_id']) ? Profile::find($data['discipler_profile_id']) : null);

        return back()->with('status', 'One 2 One started.');
    }

    public function activate(InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $this->involvement->activateMember($involvementRequest);

        return back()->with('status', "{$involvementRequest->displayName()} is now an active member.");
    }

    public function archive(InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $this->involvement->archive($involvementRequest);

        return redirect()->route('admin.involvement.index')->with('status', 'Request archived.');
    }

    public function restore(InvolvementRequest $involvementRequest): RedirectResponse
    {
        $this->authorize('update', $involvementRequest);
        $this->involvement->restore($involvementRequest);

        return back()->with('status', 'Request restored.');
    }

    private function filtered(Request $request): Builder
    {
        $status = $request->string('status')->value();

        return $this->scope->involvementRequests(InvolvementRequest::query(), $request->user())
            ->when($request->boolean('archived'), fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->whereNull('archived_at'))
            ->when($status === 'not_contacted', fn ($q) => $q->where('status', InvolvementStatus::New->value)->whereNull('contacted_at'))
            ->when($status && $status !== 'not_contacted', fn ($q) => $q->where('status', $status))
            ->when($request->filled('interest'), fn ($q) => $q->whereHas('interests', fn ($i) => $i->where('involvement_interests.id', $request->integer('interest'))))
            ->when($request->boolean('ministry_interest'), fn ($q) => $q->where(fn ($w) => $w->withInterestAction(InterestAction::Volunteer)->orWhereHas('interests', fn ($i) => $i->where('action', InterestAction::Ministry->value))))
            ->when($request->boolean('lifegroup_interest'), fn ($q) => $q->withInterestAction(InterestAction::Lifegroup))
            ->when($request->filled('campus'), fn ($q) => $q->where('campus_id', $request->integer('campus')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->value();
                $digits = ltrim(preg_replace('/\D/', '', $term), '0');

                $q->where(fn ($w) => $w->where('full_name', 'like', "%{$term}%")
                    ->orWhere('nickname', 'like', "%{$term}%")
                    ->when(strlen($digits) >= 4, fn ($w) => $w->orWhere('whatsapp', 'like', "%{$digits}%")));
            });
    }
}
