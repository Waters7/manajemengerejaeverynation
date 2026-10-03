<x-layouts.admin :title="$announcement->exists ? 'Edit announcement' : 'New announcement'">
    <x-page-header :title="$announcement->exists ? 'Edit announcement' : 'New announcement'" :back="route('admin.announcements.index')" />
    <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="card card-pad max-w-2xl space-y-5">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif
        <x-form.input name="title" label="Title" :value="$announcement->title" required />
        <x-form.textarea name="body" label="Message" :value="$announcement->body" rows="5" required />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select name="audience" label="Audience" :options="$audiences" :value="$announcement->audience" required />
            <x-form.input name="expires_at" type="datetime-local" label="Expires" :value="$announcement->expires_at" />
        </div>
        @include('admin.partials.publish-fields', ['model' => $announcement])
        <x-form.checkbox name="is_pinned" label="Pin to top" :checked="$announcement->is_pinned" />
        <button class="btn btn-primary">Save</button>
    </form>
</x-layouts.admin>
