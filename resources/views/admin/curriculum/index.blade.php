<x-layouts.admin title="Curriculum">
    <x-page-header title="Curriculum" description="Stage → Program → Book / Class / Training / Event → Chapter / Session. Everything here is editable — rename to match official nomenclature.">
        <a href="{{ route('admin.curriculum.programs.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New program</a>
        <x-modal name="new-stage" title="New stage">
            <x-slot:trigger><button type="button" class="btn btn-outline btn-sm">New stage</button></x-slot:trigger>
            <form method="POST" action="{{ route('admin.curriculum.stages.store') }}" class="space-y-4">
                @csrf
                <x-form.input name="name" label="Name" required />
                <x-form.input name="tagline" label="Tagline" />
                <x-form.input name="color" type="color" label="Colour" value="#0067B9" class="[&_input]:h-11 [&_input]:p-1" />
                <button class="btn btn-primary w-full">Add stage</button>
            </form>
        </x-modal>
    </x-page-header>

    <div class="space-y-6">
        @foreach ($stages as $stage)
            <div class="card overflow-hidden" x-data="{ edit: false }">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5" style="border-top: 4px solid {{ $stage->color }}">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight uppercase">{{ $loop->iteration }}. {{ $stage->name }} @unless ($stage->is_active)<x-badge color="gray">Inactive</x-badge>@endunless</h2>
                        <p class="text-sm text-muted">{{ $stage->tagline }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" x-on:click="edit = !edit" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /> Edit stage</button>
                        <a href="{{ route('admin.curriculum.programs.create', ['stage' => $stage->id]) }}" class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" /> Program</a>
                    </div>
                </div>
                <form x-show="edit" x-cloak method="POST" action="{{ route('admin.curriculum.stages.update', $stage) }}" class="grid gap-3 border-b border-line bg-slate-50 p-5 sm:grid-cols-6 sm:items-end">
                    @csrf @method('PUT')
                    <x-form.input name="name" label="Name" :value="$stage->name" required class="sm:col-span-2" />
                    <x-form.input name="tagline" label="Tagline" :value="$stage->tagline" class="sm:col-span-2" />
                    <x-form.input name="color" type="color" label="Colour" :value="$stage->color" class="[&_input]:h-11 [&_input]:p-1" />
                    <x-form.input name="sequence" type="number" label="Order" :value="$stage->sequence" />
                    <x-form.textarea name="description" label="Description" :value="$stage->description" rows="2" class="sm:col-span-4" />
                    <x-form.checkbox name="is_active" label="Active" :checked="$stage->is_active" />
                    <button class="btn btn-dark btn-sm">Save stage</button>
                </form>
                <table class="table">
                    <thead><tr><th>#</th><th>Program</th><th>Type</th><th>Units</th><th>Prerequisite</th><th>Active</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($stage->programs as $program)
                            <tr>
                                <td class="text-muted">{{ $program->sequence }}</td>
                                <td><span class="font-bold">{{ $program->name }}</span> @if ($program->is_milestone)<x-badge color="amber">Milestone</x-badge>@endif @unless ($program->is_required)<x-badge color="gray">Optional</x-badge>@endunless</td>
                                <td><x-badge :value="$program->type" /></td>
                                <td>{{ $program->chapters_count ?: $program->total_sessions }} {{ $program->type === \App\Enums\ProgramType::Book ? 'chapters' : 'sessions' }}</td>
                                <td class="text-sm">{{ $program->prerequisite?->name ?? '—' }}</td>
                                <td>{{ $program->active_count }} people</td>
                                <td><x-badge :color="$program->status === 'active' ? 'green' : 'gray'">{{ ucfirst($program->status) }}</x-badge></td>
                                <td class="text-right"><a href="{{ route('admin.curriculum.programs.edit', $program) }}" class="btn btn-outline btn-sm">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-sm text-muted">No programs in this stage yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
