<x-layouts.admin :title="$profile->exists ? 'Edit '.$profile->full_name : 'Add person'">
    <x-page-header :title="$profile->exists ? 'Edit '.$profile->full_name : 'Add person'" :back="$profile->exists ? route('admin.members.show', $profile) : route('admin.members.index')" />

    <form method="POST" action="{{ $profile->exists ? route('admin.members.update', $profile) : route('admin.members.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($profile->exists) @method('PUT') @endif

        <div class="card card-pad space-y-5 lg:col-span-2">
            <h2 class="font-extrabold uppercase">Personal</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="full_name" label="Full name" :value="$profile->full_name" required />
                <x-form.input name="nickname" label="Nickname" :value="$profile->nickname" />
                <x-form.select name="gender" label="Gender" :options="$genders" :value="$profile->gender" placeholder="—" />
                <x-form.input name="birth_date" type="date" label="Birth date" :value="$profile->birth_date" />
                <x-form.input name="whatsapp" type="tel" label="WhatsApp" :value="\App\Services\WhatsApp::display($profile->whatsapp)" />
                <x-form.input name="email" type="email" label="Email" :value="$profile->email" />
                <x-form.input name="area" label="Area" :value="$profile->area" />
                <x-form.select name="life_stage" label="Life stage" :options="$lifeStages" :value="$profile->life_stage" placeholder="—" />
                <x-form.input name="occupation" label="Occupation" :value="$profile->occupation" />
                <x-form.input name="company" label="Company" :value="$profile->company" />
                <x-form.select name="campus_id" label="Campus (Campus Ministry)" :options="$campuses" :value="$profile->campus_id" placeholder="—" />
                <x-form.input name="school_name" label="School / campus name" :value="$profile->school_name" />
            </div>
            <x-form.textarea name="address" label="Address" :value="$profile->address" rows="2" />
        </div>

        <div class="space-y-6">
            <div class="card card-pad space-y-5">
                <h2 class="font-extrabold uppercase">Church</h2>
                <x-form.select name="member_status" label="Member status" :options="$statuses" :value="$profile->member_status" required />
                <x-form.input name="first_visit_date" type="date" label="First visit" :value="$profile->first_visit_date" />
                <x-form.input name="join_date" type="date" label="Join date" :value="$profile->join_date" />
                <x-form.select name="source" label="How they found us" :options="$sources" :value="$profile->source" placeholder="—" />
            </div>
            <div class="card card-pad">
                <x-form.image name="photo" label="Photo" :current="$profile->photoUrl()" />
            </div>
            <button class="btn btn-primary w-full">{{ $profile->exists ? 'Save changes' : 'Add person' }}</button>
        </div>
    </form>
</x-layouts.admin>
