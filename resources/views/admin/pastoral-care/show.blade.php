<x-layouts.admin title="Pastoral Care">
    <x-page-header :title="$care->subject" :eyebrow="($care->category?->name ?? 'Pastoral care').' · opened '.$care->created_at->translatedFormat('j M Y').($care->opener ? ' by '.$care->opener->name : '')" :back="route('admin.pastoral-care.index')">
        <x-badge :value="$care->priority" /><x-badge :value="$care->status" />
        @can('update', $care)<a href="{{ route('admin.pastoral-care.edit', $care) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>@endcan
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad">
                <p class="flex items-center gap-2 text-xs text-muted"><x-icon name="lock" class="size-3.5" /> Confidential</p>
                <p class="mt-3 whitespace-pre-line text-slate-800">{{ $care->description ?: 'No details recorded.' }}</p>
            </div>
            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Pastoral notes</h2>
                @can('update', $care)
                    <form method="POST" action="{{ route('admin.pastoral-care.notes.store', $care) }}" class="mt-4 space-y-2">
                        @csrf
                        <x-form.textarea name="body" rows="3" placeholder="Visit, call, prayer, next step…" required />
                        <button class="btn btn-dark btn-sm">Add note</button>
                    </form>
                @endcan
                <ul class="mt-6 space-y-4">
                    @forelse ($care->notes as $note)
                        <li class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-xs text-muted"><strong class="text-ink">{{ $note->author?->name }}</strong> · {{ $note->created_at->translatedFormat('j M Y H:i') }}</p>
                            <p class="mt-1 text-sm whitespace-pre-line">{{ $note->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-muted">No notes yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="card card-pad h-fit">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-xs font-bold text-muted uppercase">Person</dt><dd class="font-semibold">@if ($care->profile)<a href="{{ route('admin.members.show', $care->profile) }}" class="hover:text-brand">{{ $care->profile->full_name }}</a>@else{{ $care->requester_name ?? '—' }}@endif</dd></div>
                <div><dt class="text-xs font-bold text-muted uppercase">Contact</dt><dd class="font-semibold">{{ $care->contact ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold text-muted uppercase">Assigned pastor</dt><dd class="font-semibold">{{ $care->assignee?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold text-muted uppercase">Resolved</dt><dd class="font-semibold">{{ $care->resolved_at?->translatedFormat('j M Y') ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>
</x-layouts.admin>
