{{--
    4E journey checklist.
    $stages: output of JourneyService::journey()
    $progressLink (optional): fn (MemberProgramProgress $progress) => url for managing a program
--}}
<div class="grid gap-4 md:grid-cols-2">
    @foreach ($stages as $row)
        @php
            $programs = $row['programs'];
            $stageDone = $programs->isNotEmpty() && $programs->every(fn ($p) => $p['status'] === \App\Enums\ProgressStatus::Completed);
            $stageActive = $programs->contains(fn ($p) => $p['status'] === \App\Enums\ProgressStatus::InProgress);
        @endphp
        <div @class(['rounded-2xl border p-5', 'border-brand bg-brand-50/50' => $stageActive, 'border-line bg-white' => ! $stageActive])>
            <div class="flex items-center justify-between gap-3">
                <h3 class="flex items-center gap-2 text-sm font-extrabold tracking-[0.15em] uppercase">
                    <span class="size-2.5 rounded-full" style="background: {{ $row['stage']->color }}"></span>
                    {{ $row['stage']->name }}
                </h3>
                @if ($stageDone)
                    <x-badge color="green">Completed</x-badge>
                @elseif ($stageActive)
                    <x-badge color="blue">Current</x-badge>
                @endif
            </div>
            <ul class="mt-4 space-y-3">
                @foreach ($programs as $item)
                    @php $status = $item['status']; @endphp
                    <li class="flex items-start gap-3">
                        @if ($status === \App\Enums\ProgressStatus::Completed)
                            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-success text-white"><x-icon name="check" class="size-3.5" /></span>
                        @elseif ($status === \App\Enums\ProgressStatus::InProgress)
                            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-2 border-brand"><span class="size-2 rounded-full bg-brand"></span></span>
                        @else
                            <span class="mt-0.5 size-5 shrink-0 rounded-full border-2 border-slate-300"></span>
                        @endif
                        <div class="min-w-0 grow">
                            <div class="flex flex-wrap items-center justify-between gap-x-3">
                                @if (isset($progressLink) && $item['progress'])
                                    <a href="{{ $progressLink($item['progress']) }}" class="text-sm font-bold text-ink hover:text-brand">{{ $item['program']->name }}</a>
                                @else
                                    <span @class(['text-sm font-bold', 'text-ink' => $status !== \App\Enums\ProgressStatus::NotStarted, 'text-slate-400' => $status === \App\Enums\ProgressStatus::NotStarted])>{{ $item['program']->name }}</span>
                                @endif
                                @if ($item['progress']?->completed_at)
                                    <span class="text-xs text-muted">{{ $item['progress']->completed_at->translatedFormat('M Y') }}</span>
                                @endif
                            </div>
                            @if ($status === \App\Enums\ProgressStatus::InProgress && $item['total'] > 0)
                                <x-progress :value="$item['done']" :total="$item['total']" class="mt-1.5" />
                            @elseif ($status === \App\Enums\ProgressStatus::Incomplete)
                                <x-badge :value="$status" class="mt-1" />
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
