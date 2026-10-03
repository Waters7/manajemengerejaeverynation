<x-layouts.admin :title="$ministry->exists ? 'Edit '.$ministry->name : 'New ministry'">
    <x-page-header :title="$ministry->exists ? 'Edit '.$ministry->name : 'New ministry'" :back="$ministry->exists ? route('admin.ministries.show', $ministry) : route('admin.ministries.index')" />

    <form method="POST" action="{{ $ministry->exists ? route('admin.ministries.update', $ministry) : route('admin.ministries.store') }}" class="card card-pad max-w-2xl space-y-5">
        @csrf
        @if ($ministry->exists) @method('PUT') @endif
        <x-form.input name="name" label="Name" :value="$ministry->name" required />
        <x-form.textarea name="description" label="Description" :value="$ministry->description" rows="3" />
        @if (auth()->user()->hasChurchWideAccess())
            <x-form.select name="coordinator_id" label="Coordinator" :options="$coordinators" :value="$ministry->coordinator_id" placeholder="—" hint="Only accounts with the Ministry Coordinator role appear here." />
        @endif
        <div class="grid grid-cols-2 gap-4">
            <x-form.input name="sort_order" type="number" label="Order" :value="$ministry->sort_order ?? 0" />
            <x-form.input name="color" type="color" label="Colour" :value="$ministry->color ?? '#0067B9'" class="[&_input]:h-11 [&_input]:p-1" />
        </div>
        <x-form.checkbox name="is_active" label="Active" :checked="$ministry->is_active" />
        <x-form.checkbox name="accepting_volunteers" label="Accepting volunteers (shown on Get Involved / Serve forms)" :checked="$ministry->accepting_volunteers" />
        <button class="btn btn-primary">{{ $ministry->exists ? 'Save' : 'Create ministry' }}</button>
    </form>

    @if ($ministry->exists)
        @can('delete', $ministry)
            <form method="POST" action="{{ route('admin.ministries.destroy', $ministry) }}" data-confirm="Delete this ministry?" class="mt-4">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /> Delete ministry</button>
            </form>
        @endcan
    @endif
</x-layouts.admin>
