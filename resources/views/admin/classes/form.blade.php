<x-layouts.admin :title="$batch->exists ? 'Edit batch' : 'New class batch'">
    <x-page-header :title="$batch->exists ? 'Edit '.$batch->name : 'New class batch'" :back="$batch->exists ? route('admin.classes.show', $batch) : route('admin.classes.index')" />

    <form method="POST" action="{{ $batch->exists ? route('admin.classes.update', $batch) : route('admin.classes.store') }}" class="card card-pad max-w-3xl space-y-5">
        @csrf
        @if ($batch->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="discipleship_program_id" label="Program" :options="$programs" :value="$batch->discipleship_program_id" placeholder="Choose…" required />
            <x-form.input name="name" label="Batch name" :value="$batch->name" placeholder="e.g. Batch October 2026" required />
            <x-form.input name="start_date" type="date" label="Start date" :value="$batch->start_date" />
            <x-form.input name="end_date" type="date" label="End date" :value="$batch->end_date" />
            <x-form.select name="facilitator_profile_id" label="Facilitator" :options="$facilitators" :value="$batch->facilitator_profile_id" placeholder="—" />
            <x-form.input name="location" label="Location" :value="$batch->location" />
            <x-form.input name="capacity" type="number" label="Capacity" :value="$batch->capacity" min="1" />
            <x-form.select name="campus_id" label="Campus" :options="$campuses" :value="$batch->campus_id" placeholder="— Church-wide —" />
            <x-form.select name="registration_status" label="Registration" :options="['open' => 'Open', 'closed' => 'Closed']" :value="$batch->registration_status" required />
            <x-form.select name="status" label="Status" :options="$statuses" :value="$batch->status" required />
        </div>
        <x-form.textarea name="notes" label="Notes" :value="$batch->notes" rows="3" />
        @unless ($batch->exists)
            <x-form.checkbox name="generate_sessions" label="Generate sessions from the curriculum" :checked="true" />
        @endunless
        <button class="btn btn-primary">{{ $batch->exists ? 'Save batch' : 'Create batch' }}</button>
    </form>
</x-layouts.admin>
