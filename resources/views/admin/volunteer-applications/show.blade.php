<x-layouts.admin :title="$application->name">
    <x-page-header :title="$application->name" :eyebrow="'Volunteer application · '.$application->created_at->translatedFormat('j F Y')" :back="route('admin.volunteer-applications.index')">
        <x-wa-button :href="$waLink" label="Contact" />
        @if ($application->profile)<a href="{{ route('admin.members.show', $application->profile) }}" class="btn btn-outline btn-sm">Person profile</a>@endif
    </x-page-header>

    <div class="card mb-6 p-4">
        <form method="POST" action="{{ route('admin.volunteer-applications.update', $application) }}" class="flex flex-wrap items-end gap-3">
            @csrf @method('PATCH')
            <div class="flex flex-wrap gap-2">
                @foreach ($statuses as $value => $label)
                    <label class="chip"><input type="radio" name="status" value="{{ $value }}" class="sr-only" @checked($application->status->value === $value)> {{ $label }}</label>
                @endforeach
            </div>
            <x-form.input name="interview_at" type="datetime-local" label="Interview" :value="$application->interview_at" />
            <button class="btn btn-primary btn-sm">Update status</button>
        </form>
        <p class="mt-2 text-xs text-muted">Orientation adds them to the ministry team in orientation; Accepted makes them an active volunteer.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-pad lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                @foreach ([
                    'Ministries' => $application->ministries->pluck('name')->implode(', '),
                    'WhatsApp' => \App\Services\WhatsApp::display($application->whatsapp),
                    'Email' => $application->email,
                    'Area' => $application->area,
                    'Current church connection' => $application->church_connection,
                    'Skills' => implode(', ', $application->skills ?? []),
                    'Availability' => collect($application->availability ?? [])->map(fn ($a) => \App\Enums\Availability::tryFrom($a)?->label())->filter()->implode(', '),
                    'Reviewed by' => $application->reviewer?->name,
                ] as $term => $detail)
                    <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">{{ $term }}</dt><dd class="mt-0.5 font-semibold">{{ filled($detail) ? $detail : '—' }}</dd></div>
                @endforeach
            </dl>
            <div class="mt-6 space-y-4">
                <div><p class="text-xs font-bold tracking-wider text-muted uppercase">Experience</p><p class="mt-1 whitespace-pre-line">{{ $application->experience ?: '—' }}</p></div>
                <div><p class="text-xs font-bold tracking-wider text-muted uppercase">Why do you want to serve?</p><p class="mt-1 whitespace-pre-line">{{ $application->motivation ?: '—' }}</p></div>
            </div>
        </div>
        <div class="card card-pad">
            <h2 class="font-extrabold uppercase">Notes</h2>
            <form method="POST" action="{{ route('admin.volunteer-applications.notes.store', $application) }}" class="mt-3 space-y-2">
                @csrf
                <x-form.select name="type" :options="\App\Enums\ContactType::options()" value="note" />
                <x-form.textarea name="body" rows="2" required />
                <button class="btn btn-outline btn-sm">Add note</button>
            </form>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($application->contactNotes as $note)
                    <li class="border-l-2 border-brand-100 pl-3"><p class="text-xs text-muted">{{ $note->author?->name }} · {{ $note->type->label() }} · {{ $note->created_at->diffForHumans() }}</p><p>{{ $note->body }}</p></li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts.admin>
