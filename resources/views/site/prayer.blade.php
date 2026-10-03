<x-layouts.site title="Prayer Request" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Prayer', 'title' => "WE'D LOVE\nTO PRAY FOR YOU.", 'lead' => 'Bagikan pokok doamu. Tim kami akan berdoa bersamamu — dengan penuh kasih dan menjaga kerahasiaan.'])

    <section class="bg-canvas py-14 sm:py-20">
        <div class="mx-auto max-w-2xl px-4 sm:px-6">
            @if (session('prayed'))
                <div class="card p-10 text-center">
                    <span class="mx-auto grid size-16 place-items-center rounded-full bg-brand-50 text-brand"><x-icon name="heart" class="size-8" /></span>
                    <h2 class="mt-5 text-2xl font-extrabold">We're praying with you.</h2>
                    <p class="mt-2 text-muted">“Serahkanlah segala kekuatiranmu kepada-Nya, sebab Ia yang memelihara kamu.” — 1 Petrus 5:7</p>
                    <a href="{{ route('home') }}" class="btn btn-primary mt-8">Back to home</a>
                </div>
            @else
                <form method="POST" action="{{ route('prayer.store') }}" class="card space-y-5 p-6 sm:p-10">
                    @csrf
                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="name" label="Nama" :value="auth()->user()?->name" />
                        <x-form.input name="contact" label="Kontak (opsional)" :value="auth()->user()?->whatsapp" placeholder="WhatsApp / email" />
                    </div>
                    <x-form.textarea name="request" label="Pokok doa" rows="6" required />
                    <x-form.select name="visibility" label="Siapa yang boleh membaca?" :options="$visibilities" value="pastor_only" required />
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_anonymous" value="1" class="checkbox"> Kirim secara anonim
                    </label>
                    <button class="btn btn-primary btn-lg w-full">Send prayer request</button>
                </form>
            @endif
        </div>
    </section>
</x-layouts.site>
