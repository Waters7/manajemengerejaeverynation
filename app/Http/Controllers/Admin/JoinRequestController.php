<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Enums\JoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\LifeGroup;
use App\Models\LifeGroupJoinRequest;
use App\Services\AccessScope;
use App\Services\LifeGroupService;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * LifeGroup join requests: Pending → Contacted → Approved → Joined (or Rejected).
 * The WhatsApp group invitation is only revealed once a request is approved.
 */
class JoinRequestController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request, WhatsApp $whatsApp): View
    {
        $user = $request->user();

        $requests = $this->scope->joinRequests(LifeGroupJoinRequest::query(), $user)
            ->with(['lifeGroup', 'contactNotes.author', 'handler'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->whereIn('status', [JoinRequestStatus::Pending->value, JoinRequestStatus::Contacted->value, JoinRequestStatus::Approved->value]))
            ->when($request->filled('lifegroup'), fn ($q) => $q->where('life_group_id', $request->integer('lifegroup')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $links = $requests->getCollection()->mapWithKeys(fn (LifeGroupJoinRequest $r) => [$r->id => $whatsApp->templateLink($r->whatsapp, 'wa_template_lifegroup', [
            'nickname' => strtok($r->name, ' '),
            'name' => $r->name,
            'sender' => $user->displayName(),
            'lifegroup' => $r->lifeGroup?->name,
            'schedule' => $r->lifeGroup?->scheduleLabel(),
        ])]);

        return view('admin.join-requests.index', [
            'requests' => $requests,
            'links' => $links,
            'statuses' => JoinRequestStatus::options(),
            'groups' => $this->scope->lifeGroups(LifeGroup::query(), $user)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, LifeGroupJoinRequest $joinRequest, LifeGroupService $service): RedirectResponse
    {
        $this->authorizeRequest($request, $joinRequest);
        $data = $request->validate(['status' => ['required', Rule::enum(JoinRequestStatus::class)]]);

        $service->updateJoinRequestStatus($joinRequest, JoinRequestStatus::from($data['status']));

        return back()->with('status', 'Request updated to '.JoinRequestStatus::from($data['status'])->label().'.');
    }

    public function note(Request $request, LifeGroupJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorizeRequest($request, $joinRequest);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $joinRequest->contactNotes()->create([
            'profile_id' => $joinRequest->profile_id,
            'user_id' => $request->user()->id,
            'type' => ContactType::Note,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Note added.');
    }

    /** Opens WhatsApp with the group invitation — only after approval. */
    public function invite(Request $request, LifeGroupJoinRequest $joinRequest, WhatsApp $whatsApp, LifeGroupService $service): RedirectResponse
    {
        $this->authorizeRequest($request, $joinRequest);
        abort_unless($joinRequest->canShareInvite(), 403, 'Approve the request before sharing the WhatsApp group invitation.');
        abort_unless(filled($joinRequest->lifeGroup->whatsapp_invite_url), 422, 'This LifeGroup has no WhatsApp invite link yet.');

        $service->markInviteShared($joinRequest);

        return redirect()->away($whatsApp->templateLink($joinRequest->whatsapp, 'wa_template_lifegroup_invite', [
            'nickname' => strtok($joinRequest->name, ' '),
            'lifegroup' => $joinRequest->lifeGroup->name,
            'invite_url' => $joinRequest->lifeGroup->whatsapp_invite_url,
        ]));
    }

    private function authorizeRequest(Request $request, LifeGroupJoinRequest $joinRequest): void
    {
        abort_unless($this->scope->joinRequests(LifeGroupJoinRequest::whereKey($joinRequest->id), $request->user())->exists(), 403);
    }
}
