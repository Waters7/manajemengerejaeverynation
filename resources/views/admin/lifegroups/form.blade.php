<x-layouts.admin :title="$group->exists ? 'Edit '.$group->name : 'New LifeGroup'">
    <x-page-header :title="$group->exists ? 'Edit '.$group->name : 'New LifeGroup'" :back="$group->exists ? route('admin.lifegroups.show', $group) : route('admin.lifegroups.index')" />

    <form method="POST" action="{{ $group->exists ? route('admin.lifegroups.update', $group) : route('admin.lifegroups.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($group->exists) @method('PUT') @endif
        <div class="card card-pad space-y-5 lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Name" :value="$group->name" required class="sm:col-span-2" />
                <x-form.select name="category" label="Category" :options="$categories" :value="$group->category" required />
                <x-form.input name="area" label="Area" :value="$group->area" />
                <x-form.select name="meeting_day" label="Day" :options="$days" :value="$group->meeting_day" placeholder="—" />
                <x-form.input name="meeting_time" type="time" label="Time" :value="$group->meeting_time ? substr($group->meeting_time, 0, 5) : null" />
                <x-form.input name="location" label="Location" :value="$group->location" class="sm:col-span-2" />
            </div>
            <x-form.textarea name="description" label="Description (public)" :value="$group->description" rows="4" />
            @if ($canChangeLeader)
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="leader_profile_id" label="Leader" :options="$leaders" :value="$group->leader_profile_id" placeholder="—" />
                    <x-form.select name="co_leader_profile_id" label="Co-leader" :options="$leaders" :value="$group->co_leader_profile_id" placeholder="—" />
                    <x-form.select name="campus_id" label="Campus" :options="$campuses" :value="$group->campus_id" placeholder="— Not a campus group —" />
                    <x-form.select name="parent_id" label="Multiplied from" :options="$parents" :value="$group->parent_id" placeholder="—" />
                </div>
            @endif
            <x-form.input name="whatsapp_invite_url" type="url" label="WhatsApp group invite link" :value="$group->whatsapp_invite_url" hint="Never shown publicly — only shared after a join request is approved." />
        </div>
        <div class="space-y-6">
            <div class="card card-pad space-y-4">
                @if ($canChangeLeader)
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'multiplied' => 'Multiplied']" :value="$group->status" required />
                @else
                    <input type="hidden" name="status" value="{{ $group->status }}">
                @endif
                <x-form.input name="capacity" type="number" label="Capacity" :value="$group->capacity" min="1" />
                <x-form.input name="launched_at" type="date" label="Launched" :value="$group->launched_at" />
                <x-form.checkbox name="accepting_members" label="Accepting new members" :checked="$group->accepting_members" />
                <x-form.checkbox name="is_public" label="Show on public website" :checked="$group->is_public" />
            </div>
            <div class="card card-pad"><x-form.image name="cover" label="Cover" :current="$group->coverUrl()" /></div>
            <button class="btn btn-primary w-full">{{ $group->exists ? 'Save changes' : 'Create LifeGroup' }}</button>
        </div>
    </form>
</x-layouts.admin>
