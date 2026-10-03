<x-layouts.auth title="Forgot password" heading="Reset password" subheading="Masukkan email akunmu, kami akan mengirim link untuk membuat password baru.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Email" required autofocus />
        <button class="btn btn-primary btn-lg w-full">Send reset link</button>
    </form>
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="font-bold text-brand hover:underline">← Back to login</a></p>
</x-layouts.auth>
