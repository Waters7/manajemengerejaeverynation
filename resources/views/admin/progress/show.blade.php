<x-layouts.admin :title="$progress->program->name.' — '.$progress->profile->full_name">
    <x-page-header :title="$progress->profile->full_name" :eyebrow="$progress->program->stage?->name.' · '.$progress->program->name" :back="route('admin.members.show', $progress->profile).'#discipleship'">
        <a href="{{ route('admin.members.show', $progress->profile) }}" class="btn btn-outline btn-sm">Person profile</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($canEdit)
                @livewire(\App\Livewire\ProgramTracker::class, ['progress' => $progress], key('tracker-'.$progress->id))
            @else
                <div class="card card-pad"><p class="text-sm text-muted">You can view this journey, but only the discipler or the discipleship team can update it.</p></div>
            @endif
            @if ($progress->batch)
                <a href="{{ route('admin.classes.show', $progress->batch) }}" class="card card-hover card-pad mt-4 block">
                    <p class="eyebrow">Class batch</p>
                    <p class="mt-1 font-extrabold">{{ $progress->batch->name }}</p>
                </a>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.progress.update', $progress) }}" class="card card-pad h-fit space-y-4">
            @csrf @method('PATCH')
            <h2 class="font-extrabold uppercase">Program details</h2>
            @if ($progress->program->prerequisite)
                <p class="rounded-xl bg-slate-50 p-3 text-xs text-muted">Prerequisite: <strong>{{ $progress->program->prerequisite->name }}</strong></p>
            @endif
            <fieldset @disabled(! $canEdit) class="space-y-4">
                <x-form.select name="status" label="Status" :options="$statuses" :value="$progress->status" required />
                <x-form.select name="discipler_profile_id" label="Discipler" :options="$disciplers" :value="$progress->discipler_profile_id" placeholder="—" />
                <x-form.input name="started_at" type="date" label="Start date" :value="$progress->started_at" />
                <x-form.input name="expected_completion_at" type="date" label="Expected completion" :value="$progress->expected_completion_at" />
                <x-form.input name="completed_at" type="date" label="Completion date" :value="$progress->completed_at" />
                <x-form.input name="next_follow_up_at" type="date" label="Next follow-up" :value="$progress->next_follow_up_at" />
                <x-form.textarea name="notes" label="Notes" :value="$progress->notes" rows="3" />
                <button class="btn btn-primary w-full">Save</button>
            </fieldset>
        </form>
    </div>
</x-layouts.admin>
