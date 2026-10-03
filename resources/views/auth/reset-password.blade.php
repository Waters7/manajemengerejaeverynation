<x-layouts.auth title="Reset password" heading="New password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email" :value="$email" required />
        <x-form.input name="password" type="password" label="Password baru" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Ulangi password" required autocomplete="new-password" />
        <button class="btn btn-primary btn-lg w-full">Save password</button>
    </form>
</x-layouts.auth>
