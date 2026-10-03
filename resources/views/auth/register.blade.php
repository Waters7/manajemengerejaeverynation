<x-layouts.auth title="Create account" heading="Join the family" subheading="Buat akun untuk mengikuti perjalanan pemuridanmu, mendaftar event, dan terhubung dengan LifeGroup.">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nama Lengkap" required autocomplete="name" />
            <x-form.input name="nickname" label="Nama Panggilan" required />
        </div>
        <x-form.input name="whatsapp" type="tel" label="Nomor WhatsApp" placeholder="0812xxxxxxxx" required autocomplete="tel" />
        <x-form.input name="email" type="email" label="Email" required autocomplete="email" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" hint="Minimal 8 karakter." />
            <x-form.input name="password_confirmation" type="password" label="Ulangi Password" required autocomplete="new-password" />
        </div>
        <button class="btn btn-primary btn-lg w-full">Create account</button>
        <p class="text-center text-xs text-muted">Dengan mendaftar, data kamu hanya digunakan oleh tim Every Nation Bekasi untuk keperluan pelayanan.</p>
    </form>
    <p class="mt-8 text-center text-sm text-muted">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-brand hover:underline">Login</a>
    </p>
</x-layouts.auth>
