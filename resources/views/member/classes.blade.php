<x-layouts.member title="My Classes">
    <x-page-header title="My classes" description="Kelas discipleship yang sedang dan pernah kamu ikuti." />

    <div class="space-y-4">
        @forelse ($participations as $participation)
            @php
                $batch = $participation->batch;
                $present = $participation->attendances->where('status', \App\Enums\AttendanceStatus::Present)->count();
            @endphp
            <div class="card card-pad">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="eyebrow">{{ $batch->program->name }}</p>
                        <h2 class="mt-1 text-lg font-extrabold">{{ $batch->name }}</h2>
                        <p class="mt-1 text-sm text-muted">
                            {{ $batch->start_date?->translatedFormat('j M Y') }}@if ($batch->end_date) – {{ $batch->end_date->translatedFormat('j M Y') }}@endif
                            @if ($batch->location) · {{ $batch->location }}@endif
                        </p>
                    </div>
                    <x-badge :value="$participation->status" />
                </div>
                @if ($batch->sessions->isNotEmpty())
                    <x-progress :value="$present" :total="$batch->sessions->count()" class="mt-4 max-w-sm" />
                    <p class="mt-1 text-xs text-muted">Sessions attended</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($batch->sessions as $session)
                            @php $status = $participation->attendances->firstWhere('class_session_id', $session->id)?->status; @endphp
                            <span title="{{ $session->topic }}" @class([
                                'grid size-8 place-items-center rounded-lg text-xs font-bold',
                                'bg-success text-white' => $status === \App\Enums\AttendanceStatus::Present,
                                'bg-amber-100 text-amber-700' => $status === \App\Enums\AttendanceStatus::Excused,
                                'bg-slate-200 text-slate-500' => $status === \App\Enums\AttendanceStatus::Absent,
                                'border border-dashed border-slate-300 text-slate-400' => $status === null,
                            ])>{{ $session->session_number }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="card"><x-empty title="No classes yet" icon="academic">Lihat kelas yang sedang membuka pendaftaran di bawah.</x-empty></div>
        @endforelse
    </div>

    <h2 class="mt-12 text-lg font-extrabold uppercase">Open for registration</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @forelse ($open as $batch)
            <div class="card card-pad flex flex-col">
                <p class="eyebrow">{{ $batch->program->stage?->name }} · {{ $batch->program->type->label() }}</p>
                <p class="mt-1 text-lg font-extrabold">{{ $batch->program->name }}</p>
                <p class="text-sm text-muted">{{ $batch->name }} · mulai {{ $batch->start_date?->translatedFormat('j M Y') ?? 'TBA' }}</p>
                @if ($batch->facilitator)<p class="mt-1 text-sm text-muted">Facilitator: {{ $batch->facilitator->displayName() }}</p>@endif
                <form method="POST" action="{{ route('member.classes.register', $batch) }}" class="mt-auto pt-4">
                    @csrf
                    <button class="btn btn-primary btn-sm">Register</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-muted">Belum ada kelas yang membuka pendaftaran.</p>
        @endforelse
    </div>
</x-layouts.member>
