<x-layouts.site title="Get Involved" :transparent="true">
    @php
        $ministryTriggers = $interests->filter->revealsMinistries()->pluck('id')->values();
        $prayerTriggers = $interests->where('action', \App\Enums\InterestAction::Prayer)->pluck('id')->values();
        $user = auth()->user();
    @endphp

    @include('site.partials.page-hero', ['eyebrow' => 'Get Involved', 'title' => $settings->get('get_involved_heading'), 'lead' => $settings->get('get_involved_subheading')])

    <section class="bg-canvas py-14 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <form method="POST" action="{{ route('get-involved.store') }}" class="space-y-6 lg:col-span-2"
                x-data="{
                    selected: @js(array_map('intval', old('interests', []))),
                    ministryTriggers: @js($ministryTriggers),
                    prayerTriggers: @js($prayerTriggers),
                    source: @js(old('source', '')),
                    get showMinistry() { return this.selected.some(id => this.ministryTriggers.includes(id)) },
                    get showPrayer() { return this.selected.some(id => this.prayerTriggers.includes(id)) },
                }">
                @csrf
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                {{-- 1. About you --}}
                <div class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-full bg-brand text-sm font-extrabold text-white">1</span>
                        <h2 class="text-xl font-extrabold uppercase">Tentang kamu</h2>
                    </div>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <x-form.input name="full_name" label="Nama Lengkap" :value="$user?->name" required autocomplete="name" />
                        <x-form.input name="nickname" label="Nama Panggilan" :value="$user?->nickname" required />
                        <x-form.select name="gender" label="Jenis Kelamin" :options="$genders" placeholder="Pilih…" />
                        <x-form.input name="birth_date" type="date" label="Tanggal Lahir" />
                        <x-form.input name="whatsapp" type="tel" label="Nomor WhatsApp" :value="$user?->whatsapp" placeholder="0812xxxxxxxx" required autocomplete="tel" />
                        <x-form.input name="email" type="email" label="Email" :value="$user?->email" autocomplete="email" />
                        <x-form.input name="area" label="Area Tempat Tinggal" placeholder="Contoh: Bekasi Barat" />
                        <x-form.input name="occupation" label="Pekerjaan" />
                        <x-form.input name="company" label="Perusahaan (opsional)" />
                        @if ($campuses->isNotEmpty())
                            <x-form.select name="campus_id" label="Universitas/Kampus (opsional)" :options="$campuses" placeholder="Pilih kampus atau isi di bawah" />
                        @endif
                        <x-form.input name="campus_name" :label="$campuses->isNotEmpty() ? 'Kampus lainnya' : 'Universitas/Kampus (opsional)'" />
                    </div>

                    <fieldset class="mt-7">
                        <legend class="label">Status</legend>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach ($lifeStages as $value => $label)
                                <label class="chip"><input type="radio" name="life_stage" value="{{ $value }}" class="sr-only" @checked(old('life_stage') === $value)> {{ $label }}</label>
                            @endforeach
                        </div>
                        @error('life_stage')<p class="error">{{ $message }}</p>@enderror
                    </fieldset>

                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <x-form.select name="source" label="Bagaimana kamu pertama kali mengetahui Every Nation Bekasi?" :options="$sources" placeholder="Pilih…" x-model="source" class="sm:col-span-2" />
                        <div x-show="source === 'other'" x-cloak class="sm:col-span-2">
                            <x-form.input name="source_other" label="Ceritakan lebih lanjut" />
                        </div>
                    </div>
                </div>

                {{-- 2. Interests --}}
                <div class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-full bg-brand text-sm font-extrabold text-white">2</span>
                        <h2 class="text-xl font-extrabold uppercase">Saya tertarik untuk…</h2>
                    </div>
                    <p class="mt-2 text-sm text-muted">Boleh pilih lebih dari satu.</p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($interests as $interest)
                            <label class="choice">
                                <input type="checkbox" name="interests[]" value="{{ $interest->id }}" class="checkbox mt-0.5" x-model.number="selected">
                                <span>
                                    {{ $interest->name }}
                                    @if ($interest->description)<span class="mt-0.5 block text-xs font-normal text-muted">{{ $interest->description }}</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('interests')<p class="error">{{ $message }}</p>@enderror

                    <div x-show="showPrayer" x-cloak x-transition class="mt-6">
                        <x-form.textarea name="prayer_request" label="Prayer request" rows="3" hint="Hanya dibaca oleh pastor kami." />
                    </div>
                </div>

                {{-- 3. Ministry interest --}}
                <div class="card p-6 sm:p-8" x-show="showMinistry" x-cloak x-transition>
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-full bg-brand text-sm font-extrabold text-white">3</span>
                        <h2 class="text-xl font-extrabold uppercase">Ministry interest</h2>
                    </div>
                    <p class="mt-2 text-sm text-muted">Pilih pelayanan yang ingin kamu ikuti.</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($ministries as $ministry)
                            <label class="chip"><input type="checkbox" name="ministries[]" value="{{ $ministry->id }}" class="sr-only" @checked(in_array($ministry->id, old('ministries', [])))> {{ $ministry->name }}</label>
                        @endforeach
                    </div>
                    @error('ministries')<p class="error">{{ $message }}</p>@enderror

                    <div class="mt-7 grid gap-5">
                        <x-form.textarea name="experience" label="Experience" rows="3" placeholder="Pengalaman pelayanan atau keahlian yang relevan" />
                        <div x-data="tagInput(@js(old('skills', [])))">
                            <label class="label">Skills</label>
                            <div class="input flex flex-wrap items-center gap-2 !py-2">
                                <template x-for="(tag, i) in tags" :key="tag">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">
                                        <span x-text="tag"></span>
                                        <button type="button" x-on:click="remove(i)" class="hover:text-danger">×</button>
                                        <input type="hidden" name="skills[]" :value="tag">
                                    </span>
                                </template>
                                <input type="text" x-model="draft" x-on:keydown.enter.prevent="add()" x-on:keydown.comma.prevent="add()" x-on:blur="add()" placeholder="Ketik lalu Enter, misal: Gitar, Canva" class="min-w-40 grow border-0 p-0 text-sm focus:ring-0">
                            </div>
                        </div>
                        <fieldset>
                            <legend class="label">Availability</legend>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach ($availability as $value => $label)
                                    <label class="chip"><input type="checkbox" name="availability[]" value="{{ $value }}" class="sr-only" @checked(in_array($value, old('availability', [])))> {{ $label }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-form.textarea name="motivation" label="Kenapa kamu ingin terlibat?" rows="3" />
                    </div>
                </div>

                <div class="card p-6 sm:p-8">
                    <label class="inline-flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="consent" value="1" class="checkbox mt-0.5" @checked(old('consent')) required>
                        <span>Saya setuju data ini digunakan oleh tim Every Nation Bekasi untuk menghubungi saya dan keperluan pelayanan. Data tidak akan dibagikan kepada pihak lain.</span>
                    </label>
                    @error('consent')<p class="error">{{ $message }}</p>@enderror
                    <button class="btn btn-primary btn-lg mt-6 w-full sm:w-auto">Submit — Let's connect</button>
                </div>
            </form>

            <aside class="space-y-4">
                <div class="card sticky top-24 p-6 sm:p-8">
                    <p class="eyebrow">What happens next?</p>
                    <ol class="mt-5 space-y-5">
                        @foreach ([
                            ['We say hi', 'Seseorang dari tim kami akan menyapamu via WhatsApp.'],
                            ['Get connected', 'Kami bantu kamu menemukan LifeGroup atau langkah yang paling cocok.'],
                            ['Grow together', 'Mulai One 2 One, ikut kelas, atau mulai melayani — bersama-sama.'],
                        ] as [$step, $text])
                            <li class="flex gap-3">
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-extrabold text-brand">{{ $loop->iteration }}</span>
                                <span><strong class="block">{{ $step }}</strong><span class="text-sm text-muted">{{ $text }}</span></span>
                            </li>
                        @endforeach
                    </ol>
                    <div class="mt-8 rounded-2xl bg-soft p-4 text-sm">
                        Ingin melayani? <a href="{{ route('get-involved.serve') }}" class="font-bold text-brand">Serve With Us →</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>
