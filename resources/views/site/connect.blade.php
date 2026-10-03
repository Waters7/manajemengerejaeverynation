<x-layouts.site title="Connect Card">
    @php $prayerIds = $interests->where('action', \App\Enums\InterestAction::Prayer)->pluck('id')->values(); @endphp
    <section class="min-h-[calc(100vh-4.5rem)] bg-brand py-10 sm:py-16">
        <div class="mx-auto max-w-lg px-4">
            <div class="text-center text-white">
                <img src="{{ asset('images/mark-white.png') }}" alt="" class="mx-auto h-12 w-auto">
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight uppercase">Welcome home!</h1>
                <p class="mt-2 text-white/80">Senang kamu ada di sini. Isi Connect Card ini supaya kami bisa mengenalmu.</p>
            </div>

            <form method="POST" action="{{ route('connect.store') }}" class="card mt-8 space-y-5 p-6 sm:p-8"
                x-data="{ selected: @js(array_map('intval', old('interests', []))), prayer: @js($prayerIds) }">
                @csrf
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                <x-form.input name="full_name" label="Nama" required autocomplete="name" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="nickname" label="Nickname" />
                    <x-form.input name="birth_date" type="date" label="Birth date" />
                </div>
                <x-form.input name="whatsapp" type="tel" label="WhatsApp" placeholder="0812xxxxxxxx" required autocomplete="tel" />
                <x-form.input name="email" type="email" label="Email" autocomplete="email" />
                <x-form.input name="area" label="Area" placeholder="Contoh: Bekasi Timur" />

                <fieldset>
                    <legend class="label">Saya ingin:</legend>
                    <div class="mt-1 grid gap-2">
                        @foreach ($interests as $interest)
                            <label class="choice !py-3">
                                <input type="checkbox" name="interests[]" value="{{ $interest->id }}" class="checkbox mt-0.5" x-model.number="selected">
                                {{ $interest->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div x-show="selected.some(id => prayer.includes(id))" x-cloak x-transition>
                    <x-form.textarea name="prayer_request" label="Prayer request" rows="3" hint="Hanya dibaca oleh pastor kami." />
                </div>

                <button class="btn btn-primary btn-lg w-full">Let's connect</button>
            </form>
        </div>
    </section>
</x-layouts.site>
