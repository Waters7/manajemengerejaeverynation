<x-layouts.admin :title="$event->exists ? 'Edit event' : 'New event'">
    <x-page-header :title="$event->exists ? 'Edit '.$event->title : 'New event'" :back="$event->exists ? route('admin.events.show', $event) : route('admin.events.index')" />

    <form method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($event->exists) @method('PUT') @endif
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad space-y-5">
                <x-form.input name="title" label="Title" :value="$event->title" required />
                <x-form.input name="excerpt" label="Short summary" :value="$event->excerpt" />
                <x-form.textarea name="description" label="Description" :value="$event->description" rows="10" hint="Plain text with blank lines between paragraphs, or basic HTML (p, strong, em, a, ul, li)." />
            </div>
            <div class="card card-pad grid gap-5 sm:grid-cols-2">
                <x-form.input name="starts_at" type="datetime-local" label="Start" :value="$event->starts_at" required />
                <x-form.input name="ends_at" type="datetime-local" label="End" :value="$event->ends_at" />
                <x-form.input name="location" label="Location" :value="$event->location" />
                <x-form.input name="maps_url" type="url" label="Google Maps link" :value="$event->maps_url" />
                <x-form.input name="contact_person" label="Contact person" :value="$event->contact_person" />
                <x-form.input name="contact_whatsapp" label="Contact WhatsApp" :value="\App\Services\WhatsApp::display($event->contact_whatsapp)" />
            </div>
            <div class="card card-pad space-y-4">
                <h2 class="font-extrabold uppercase">Registration</h2>
                <x-form.checkbox name="registration_enabled" label="Enable online registration (QR ticket & check-in)" :checked="$event->registration_enabled" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="capacity" type="number" label="Capacity" :value="$event->capacity" min="1" hint="Leave empty for unlimited." />
                    <x-form.input name="registration_deadline" type="datetime-local" label="Registration deadline" :value="$event->registration_deadline" />
                </div>
                <x-form.checkbox name="waiting_list_enabled" label="Use a waiting list when full" :checked="$event->waiting_list_enabled" />
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-pad space-y-4">
                @include('admin.partials.publish-fields', ['model' => $event])
                <x-form.select name="event_category_id" label="Category" :options="$categories" :value="$event->event_category_id" placeholder="—" />
                <x-form.select name="campus_id" label="Campus" :options="$campuses" :value="$event->campus_id" :placeholder="$churchWide ? '— Church-wide —' : null" />
                <x-form.select name="life_group_id" label="LifeGroup" :options="$lifeGroups" :value="$event->life_group_id" placeholder="—" />
                <x-form.checkbox name="is_featured" label="Feature on homepage" :checked="$event->is_featured" />
            </div>
            <div class="card card-pad"><x-form.image name="cover" label="Cover" :current="$event->coverUrl()" /></div>
            <button class="btn btn-primary w-full">{{ $event->exists ? 'Save event' : 'Create event' }}</button>
        </div>
    </form>

    @if ($event->exists)
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" data-confirm="Delete this event?" class="mt-6">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /> Delete event</button>
        </form>
    @endif
</x-layouts.admin>
