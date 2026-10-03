<x-layouts.admin :title="$program->exists ? $program->name : 'New program'">
    <x-page-header :title="$program->exists ? $program->name : 'New program'" :back="route('admin.curriculum.index')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $program->exists ? route('admin.curriculum.programs.update', $program) : route('admin.curriculum.programs.store') }}" class="card card-pad h-fit space-y-4">
            @csrf
            @if ($program->exists) @method('PUT') @endif
            <x-form.select name="discipleship_stage_id" label="Stage" :options="$stages" :value="$program->discipleship_stage_id" required />
            <x-form.input name="name" label="Name" :value="$program->name" required />
            <x-form.select name="type" label="Type" :options="$types" :value="$program->type" required />
            <div class="grid grid-cols-2 gap-4">
                <x-form.input name="sequence" type="number" label="Order" :value="$program->sequence ?? 1" required />
                <x-form.input name="total_sessions" type="number" label="Total sessions" :value="$program->total_sessions" hint="Used when there are no chapters." />
            </div>
            <x-form.select name="prerequisite_id" label="Prerequisite" :options="$prerequisites" :value="$program->prerequisite_id" placeholder="None" />
            <x-form.textarea name="description" label="Description" :value="$program->description" rows="3" />
            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$program->status" />
            <x-form.checkbox name="is_required" label="Required in the journey" :checked="$program->is_required" />
            <x-form.checkbox name="is_milestone" label="Discipleship milestone (e.g. Victory Weekend)" :checked="$program->is_milestone" />
            @if (! $program->exists || $program->chapters->isEmpty())
                <x-form.input name="generate_chapters" type="number" label="Generate chapters / sessions" min="0" max="60" hint="Optional: creates numbered placeholders you can rename." />
            @endif
            <button class="btn btn-primary w-full">{{ $program->exists ? 'Save program' : 'Create program' }}</button>
        </form>

        @if ($program->exists)
            <div class="card lg:col-span-2">
                <div class="border-b border-line p-5">
                    <h2 class="font-extrabold uppercase">{{ $program->type === \App\Enums\ProgramType::Book ? 'Chapters / lessons' : 'Session outline' }}</h2>
                    <p class="text-sm text-muted">Progress is counted per chapter (e.g. Purple Book 7 / 12).</p>
                </div>
                <ul class="divide-y divide-line">
                    @foreach ($program->chapters as $chapter)
                        <li class="p-4" x-data="{ edit: false }">
                            <div class="flex items-center justify-between gap-3">
                                <p><span class="mr-2 inline-grid size-7 place-items-center rounded-full bg-brand-50 text-xs font-extrabold text-brand">{{ $chapter->number }}</span><strong>{{ $chapter->title }}</strong></p>
                                <div class="flex gap-1">
                                    <button type="button" x-on:click="edit = !edit" class="btn btn-ghost btn-sm">Edit</button>
                                    <form method="POST" action="{{ route('admin.curriculum.chapters.destroy', $chapter) }}" data-confirm="Remove this chapter?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                                </div>
                            </div>
                            <form x-show="edit" x-cloak method="POST" action="{{ route('admin.curriculum.chapters.update', $chapter) }}" class="mt-3 grid gap-3 sm:grid-cols-6 sm:items-end">
                                @csrf @method('PUT')
                                <x-form.input name="title" label="Title" :value="$chapter->title" required class="sm:col-span-3" />
                                <x-form.input name="sequence" type="number" label="Order" :value="$chapter->sequence" class="sm:col-span-1" />
                                <button class="btn btn-dark btn-sm sm:col-span-2">Save</button>
                                <x-form.textarea name="description" label="Description" :value="$chapter->description" rows="2" class="sm:col-span-6" />
                            </form>
                        </li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('admin.curriculum.chapters.store', $program) }}" class="flex flex-wrap gap-2 border-t border-line p-4">
                    @csrf
                    <input name="title" class="input max-w-md" placeholder="New chapter / session title" required>
                    <button class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" /> Add</button>
                </form>
            </div>
        @endif
    </div>

    @if ($program->exists)
        <form method="POST" action="{{ route('admin.curriculum.programs.destroy', $program) }}" data-confirm="Delete this program?" class="mt-6">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /> Delete program</button>
        </form>
    @endif
</x-layouts.admin>
