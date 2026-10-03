<x-layouts.admin title="Join Requests">
    <x-page-header title="LifeGroup join requests" description="People who'd love to join a LifeGroup. Contact → Approve → share the group invite → Joined." />

    <x-filter-bar>
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="Open requests" />
        <x-form.select name="lifegroup" label="LifeGroup" :options="$groups" :value="request('lifegroup')" placeholder="All" />
    </x-filter-bar>

    <div class="space-y-4">
        @forelse ($requests as $joinRequest)
            <div class="card card-pad">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-lg font-extrabold">{{ $joinRequest->name }}</p>
                            <x-badge :value="$joinRequest->status" />
                        </div>
                        <p class="mt-1 text-sm text-muted">
                            wants to join <strong class="text-ink">{{ $joinRequest->lifeGroup?->name }}</strong> · {{ $joinRequest->created_at->diffForHumans() }}
                            · {{ \App\Services\WhatsApp::display($joinRequest->whatsapp) }}
                            @if ($joinRequest->age) · {{ $joinRequest->age }} th @endif
                            @if ($joinRequest->area) · {{ $joinRequest->area }} @endif
                        </p>
                        @if ($joinRequest->notes)<p class="mt-2 rounded-xl bg-slate-50 p-3 text-sm">“{{ $joinRequest->notes }}”</p>@endif
                        @foreach ($joinRequest->contactNotes as $note)
                            <p class="mt-2 text-xs text-muted"><strong>{{ $note->author?->name }}</strong>: {{ $note->body }} · {{ $note->created_at->diffForHumans() }}</p>
                        @endforeach
                        <form method="POST" action="{{ route('admin.join-requests.notes.store', $joinRequest) }}" class="mt-3 flex max-w-lg gap-2">
                            @csrf
                            <input name="body" class="input !py-1.5 text-sm" placeholder="Add a note…" required>
                            <button class="btn btn-outline btn-sm">Note</button>
                        </form>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <x-wa-button :href="$links[$joinRequest->id] ?? null" label="Contact" />
                        @foreach ([
                            \App\Enums\JoinRequestStatus::Contacted,
                            \App\Enums\JoinRequestStatus::Approved,
                            \App\Enums\JoinRequestStatus::Joined,
                            \App\Enums\JoinRequestStatus::Rejected,
                        ] as $next)
                            @if ($joinRequest->status !== $next)
                                <form method="POST" action="{{ route('admin.join-requests.update', $joinRequest) }}" @if ($next === \App\Enums\JoinRequestStatus::Rejected) data-confirm="Close this request?" @endif>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next->value }}">
                                    <button @class(['btn btn-sm', 'btn-primary' => $next === \App\Enums\JoinRequestStatus::Approved, 'btn-success' => $next === \App\Enums\JoinRequestStatus::Joined, 'btn-outline' => $next === \App\Enums\JoinRequestStatus::Contacted, 'btn-ghost' => $next === \App\Enums\JoinRequestStatus::Rejected])>
                                        {{ match ($next) { \App\Enums\JoinRequestStatus::Contacted => 'Mark contacted', \App\Enums\JoinRequestStatus::Approved => 'Approve', \App\Enums\JoinRequestStatus::Joined => 'Mark joined', default => 'Not continuing' } }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                        @if ($joinRequest->canShareInvite() && $joinRequest->lifeGroup?->whatsapp_invite_url)
                            <a href="{{ route('admin.join-requests.invite', $joinRequest) }}" target="_blank" class="btn btn-whatsapp btn-sm"><x-icon name="link" class="size-4" /> Send group invite</a>
                        @endif
                    </div>
                </div>
                @if ($joinRequest->invite_shared_at)
                    <p class="mt-3 text-xs text-success">Group invitation shared {{ $joinRequest->invite_shared_at->diffForHumans() }}</p>
                @endif
            </div>
        @empty
            <div class="card"><x-empty title="No join requests" icon="inbox">New requests from the website will show up here.</x-empty></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
