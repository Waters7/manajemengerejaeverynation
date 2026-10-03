<x-layouts.admin title="Classes">
    <x-page-header title="Classes" description="Discipleship classes and trainings — batches, sessions and attendance.">
        @can('create', \App\Models\ClassBatch::class)
            <a href="{{ route('admin.classes.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New batch</a>
        @endcan
    </x-page-header>

    <x-filter-bar>
        <x-form.select name="program" label="Program" :options="$programs" :value="request('program')" placeholder="All" />
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status')" placeholder="Planned & ongoing" />
    </x-filter-bar>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $batch)
            <a href="{{ route('admin.classes.show', $batch) }}" class="card card-hover card-pad flex flex-col">
                <div class="flex items-start justify-between gap-2">
                    <p class="eyebrow">{{ $batch->program->stage?->name }} · {{ $batch->program->type->label() }}</p>
                    <x-badge :value="$batch->status" />
                </div>
                <h2 class="mt-2 text-lg font-extrabold">{{ $batch->program->name }}</h2>
                <p class="text-sm text-muted">{{ $batch->name }}</p>
                <div class="mt-4 space-y-1 text-sm text-muted">
                    <p class="flex items-center gap-2"><x-icon name="calendar" class="size-4" /> {{ $batch->start_date?->translatedFormat('j M Y') ?? 'TBA' }}{{ $batch->end_date ? ' – '.$batch->end_date->translatedFormat('j M Y') : '' }}</p>
                    @if ($batch->facilitator)<p class="flex items-center gap-2"><x-icon name="user" class="size-4" /> {{ $batch->facilitator->full_name }}</p>@endif
                    @if ($batch->location)<p class="flex items-center gap-2"><x-icon name="map-pin" class="size-4" /> {{ $batch->location }}</p>@endif
                </div>
                <div class="mt-auto flex items-center justify-between gap-2 pt-4 text-sm">
                    <span><strong>{{ $batch->active_participants_count }}</strong>{{ $batch->capacity ? ' / '.$batch->capacity : '' }} participants</span>
                    <span class="text-muted">{{ $batch->sessions_count }} sessions · {{ $batch->completed_count }} completed</span>
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty title="No class batches" icon="academic" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-layouts.admin>
