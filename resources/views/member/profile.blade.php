<x-layouts.member title="Profile">
    <x-page-header title="My profile" description="Data ini membantu tim kami melayanimu dengan lebih baik. Data pribadimu hanya dapat dilihat oleh tim pelayanan yang berwenang." />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('member.profile.update') }}" enctype="multipart/form-data" class="card card-pad space-y-5 lg:col-span-2">
            @csrf @method('PUT')
            <div class="flex items-center gap-4">
                <x-avatar :profile="$profile" size="size-16" />
                <label class="btn btn-outline btn-sm cursor-pointer">
                    <x-icon name="upload" class="size-4" /> Change photo
                    <input type="file" name="photo" accept="image/*" class="sr-only">
                </label>
                @error('photo')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Nama Lengkap" :value="$user->name" required />
                <x-form.input name="nickname" label="Nama Panggilan" :value="$user->nickname" required />
                <x-form.input name="email" type="email" label="Email" :value="$user->email" required />
                <x-form.input name="whatsapp" type="tel" label="WhatsApp" :value="\App\Services\WhatsApp::display($user->whatsapp)" required />
                <x-form.select name="gender" label="Jenis Kelamin" :options="$genders" :value="$profile->gender" placeholder="—" />
                <x-form.input name="birth_date" type="date" label="Tanggal Lahir" :value="$profile->birth_date" />
                <x-form.input name="area" label="Area" :value="$profile->area" />
                <x-form.select name="life_stage" label="Status" :options="$lifeStages" :value="$profile->life_stage" placeholder="—" />
                <x-form.input name="occupation" label="Pekerjaan" :value="$profile->occupation" />
                <x-form.input name="company" label="Perusahaan" :value="$profile->company" />
                <x-form.input name="school_name" label="Kampus" :value="$profile->school_name" />
            </div>
            <x-form.textarea name="address" label="Alamat" :value="$profile->address" rows="2" />
            <button class="btn btn-primary">Save profile</button>
        </form>

        <form method="POST" action="{{ route('member.password.update') }}" class="card card-pad h-fit space-y-5">
            @csrf @method('PUT')
            <h2 class="font-extrabold">Change password</h2>
            <x-form.input name="current_password" type="password" label="Current password" required />
            <x-form.input name="password" type="password" label="New password" required />
            <x-form.input name="password_confirmation" type="password" label="Confirm new password" required />
            <button class="btn btn-dark w-full">Update password</button>
        </form>
    </div>
</x-layouts.member>
