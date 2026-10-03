<x-layouts.admin :title="$campus->exists ? 'Edit '.$campus->name : 'Add campus'">
    <x-page-header :title="$campus->exists ? 'Edit '.$campus->name : 'Add campus'" :back="$campus->exists ? route('admin.campuses.show', $campus) : route('admin.campuses.index')" />
    <form method="POST" action="{{ $campus->exists ? route('admin.campuses.update', $campus) : route('admin.campuses.store') }}" enctype="multipart/form-data" class="grid max-w-4xl gap-6 lg:grid-cols-3">
        @csrf
        @if ($campus->exists) @method('PUT') @endif
        <div class="card card-pad space-y-5 lg:col-span-2">
            <x-form.input name="name" label="Name" :value="$campus->name" required />
            <x-form.input name="short_name" label="Short name" :value="$campus->short_name" />
            <x-form.input name="address" label="Address" :value="$campus->address" />
            <x-form.textarea name="description" label="Description" :value="$campus->description" rows="3" />
            <x-form.checkbox name="is_active" label="Active" :checked="$campus->is_active" />
            <button class="btn btn-primary">{{ $campus->exists ? 'Save' : 'Add campus' }}</button>
        </div>
        <div class="card card-pad h-fit"><x-form.image name="cover" label="Cover" :current="$campus->cover_path ? Storage::disk('public')->url($campus->cover_path) : null" /></div>
    </form>
</x-layouts.admin>
