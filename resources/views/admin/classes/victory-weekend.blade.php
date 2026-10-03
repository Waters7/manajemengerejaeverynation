<x-layouts.admin title="Victory Weekend">
    <x-page-header title="Victory Weekend" description="An event and a discipleship milestone. Track Preparing for Victory, registration, attendance and completion.">
        @if ($program)
            @can('create', \App\Models\ClassBatch::class)
                <a href="{{ route('admin.classes.create', ['program' => $program->id]) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New Victory Weekend</a>
            @endcan
        @endif
    </x-page-header>

    @unless ($program)
        <div class="card p-6 text-sm text-muted">Create a program with the slug <code>victory-weekend</code> in the curriculum to use this page.</div>
    @endunless

    <div class="space-y-6">
        @forelse ($batches as $batch)
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5">
                    <div>
                        <h2 class="text-lg font-extrabold">{{ $batch->name }}</h2>
                        <p class="text-sm text-muted">{{ $batch->start_date?->translatedFormat('j M Y') ?? 'Date TBA' }}{{ $batch->location ? ' · '.$batch->location : '' }}</p>
                    </div>
                    <div class="flex items-center gap-2"><x-badge :value="$batch->status" /><a href="{{ route('admin.classes.show', $batch) }}" class="btn btn-outline btn-sm">Manage</a></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Participant</th><th>Preparing for Victory</th><th>Registration</th><th>Attendance</th><th>Completion</th><th>Completion date</th></tr></thead>
                        <tbody>
                            @forelse ($batch->participants->sortBy('profile.full_name') as $participant)
                                @php
                                    $pfv = $pfvStatus[$participant->profile_id] ?? null;
                                    $present = $participant->attendances->where('status', \App\Enums\AttendanceStatus::Present)->count();
                                @endphp
                                <tr>
                                    <td><a href="{{ route('admin.members.show', $participant->profile) }}" class="font-bold hover:text-brand">{{ $participant->profile->full_name }}</a></td>
                                    <td>@if ($pfv)<x-badge :value="\App\Enums\ProgressStatus::from($pfv instanceof \BackedEnum ? $pfv->value : $pfv)" />@else<x-badge color="amber">Not started</x-badge>@endif</td>
                                    <td class="text-sm">{{ $participant->registered_at?->translatedFormat('j M Y') ?? '—' }}</td>
                                    <td class="text-sm">{{ $present }} / {{ $batch->sessions->count() }}</td>
                                    <td><x-badge :value="$participant->status" /></td>
                                    <td class="text-sm">{{ $participant->completed_at?->translatedFormat('j M Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-sm text-muted">No participants yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            @if ($program)<div class="card"><x-empty title="No Victory Weekend scheduled" icon="star" /></div>@endif
        @endforelse
    </div>
</x-layouts.admin>
