<x-layouts.auth title="Login" heading="Welcome back" subheading="Masuk untuk melihat journey, LifeGroup, kelas, dan pelayananmu.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <x-form.input name="email" label="Email / Username" required autofocus autocomplete="username" />
        <div>
            <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />
            <div class="mt-2 flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="checkbox"> Remember me
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand hover:underline">Lupa password?</a>
            </div>
        </div>
        <button class="btn btn-primary btn-lg w-full">Login</button>
    </form>
    <p class="mt-8 text-center text-sm text-muted">
        Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-brand hover:underline">Daftar di sini</a>
    </p>
</x-layouts.auth>
