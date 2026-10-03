<x-layouts.admin :title="$care->exists ? 'Edit care case' : 'Open care case'">
    <x-page-header :title="$care->exists ? 'Edit care case' : 'Open care case'" :back="$care->exists ? route('admin.pastoral-care.show', $care) : route('admin.pastoral-care.index')" />
    <form method="POST" action="{{ $care->exists ? route('admin.pastoral-care.update', $care) : route('admin.pastoral-care.store') }}" class="card card-pad max-w-3xl space-y-5">
        @csrf
        @if ($care->exists) @method('PUT') @endif
        <x-form.input name="subject" label="Subject" :value="$care->subject" required />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="profile_id" label="Person" :options="$people" :value="$care->profile_id" placeholder="— Not in the system —" />
            <x-form.input name="requester_name" label="Name (if not in the system)" :value="$care->requester_name" />
            <x-form.input name="contact" label="Contact" :value="$care->contact" />
            <x-form.select name="pastoral_care_category_id" label="Category" :options="$categories" :value="$care->pastoral_care_category_id" placeholder="—" />
            <x-form.select name="priority" label="Priority" :options="$priorities" :value="$care->priority" required />
            <x-form.select name="status" label="Status" :options="$statuses" :value="$care->status" required />
            <x-form.select name="assigned_to" label="Assigned pastor" :options="$team" :value="$care->assigned_to" placeholder="—" />
        </div>
        <x-form.textarea name="description" label="Details (encrypted)" :value="$care->description" rows="6" />
        <button class="btn btn-primary">Save</button>
    </form>
</x-layouts.admin>
