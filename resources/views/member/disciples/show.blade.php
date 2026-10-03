<x-layouts.member :title="$profile->full_name">
    <x-page-header :title="$profile->full_name" :back="route('member.disciples')" :description="($profile->currentStage?->name ?? 'Starting').($profile->currentProgram ? ' · '.$profile->currentProgram->name : '')">
        <x-wa-button :href="app(\App\Services\WhatsApp::class)->link($profile->whatsapp, 'Hi '.$profile->displayName().'!')" label="WhatsApp" />
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @foreach ($activeProgress as $progress)
                @livewire(\App\Livewire\ProgramTracker::class, ['progress' => $progress], key('tracker-'.$progress->id))
            @endforeach

            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">4E journey</h2>
                <div class="mt-4">@include('partials.journey', ['stages' => $stages])</div>
                <form method="POST" action="{{ route('member.disciples.programs.store', $profile) }}" class="mt-6 flex flex-wrap items-end gap-3 border-t border-line pt-5">
                    @csrf
                    <x-form.select name="program_id" label="Start next program" :options="$programs->pluck('name', 'id')" placeholder="Choose…" required class="min-w-56 grow" />
                    <x-form.input name="expected_completion_at" type="date" label="Target completion" />
                    <button class="btn btn-primary">Start</button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            @if ($relationship)
                <form method="POST" action="{{ route('member.disciples.meetings.store', $profile) }}" class="card card-pad space-y-4">
                    @csrf
                    <h2 class="font-extrabold uppercase">Add meeting</h2>
                    <x-form.input name="met_on" type="date" label="Date" :value="today()" required />
                    <x-form.input name="topic" label="Topic" />
                    <x-form.textarea name="notes" label="Notes" rows="3" />
                    <x-form.input name="next_follow_up_at" type="date" label="Schedule follow-up" />
                    <button class="btn btn-dark w-full">Save meeting</button>
                </form>

                <div class="card card-pad">
                    <h2 class="font-extrabold uppercase">Meetings</h2>
                    <ul class="mt-4 space-y-3">
                        @forelse ($relationship->meetings->take(8) as $meeting)
                            <li class="rounded-xl bg-slate-50 p-3 text-sm">
                                <p class="font-bold">{{ $meeting->met_on->translatedFormat('j M Y') }} · {{ $meeting->topic ?: 'Meeting' }}</p>
                                @if ($meeting->notes)<p class="mt-1 text-muted">{{ $meeting->notes }}</p>@endif
                            </li>
                        @empty
                            <li class="text-sm text-muted">No meetings recorded yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endif

            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Notes</h2>
                <form method="POST" action="{{ route('member.disciples.notes.store', $profile) }}" class="mt-4 space-y-3">
                    @csrf
                    <x-form.select name="type" :options="\App\Enums\ContactType::options()" value="note" />
                    <x-form.textarea name="body" rows="3" placeholder="Private note (only visible to you and the ministry team)" required />
                    <button class="btn btn-outline btn-sm">Add note</button>
                </form>
                <ul class="mt-5 space-y-3">
                    @foreach ($notes as $note)
                        <li class="border-l-2 border-brand-100 pl-3 text-sm">
                            <p class="text-xs text-muted">{{ $note->type->label() }} · {{ $note->author?->displayName() }} · {{ $note->created_at->diffForHumans() }}</p>
                            <p class="whitespace-pre-line">{{ $note->body }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.member>
