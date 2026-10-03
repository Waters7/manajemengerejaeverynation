<x-layouts.site title="Serve With Us" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Get Involved', 'title' => "SERVE\nWITH US.", 'lead' => 'Setiap talenta berharga. Mari melayani Tuhan dan sesama bersama tim pelayanan Every Nation Bekasi.'])

    <section class="bg-canvas py-14 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <form method="POST" action="{{ route('get-involved.serve.store') }}" class="card space-y-6 p-6 sm:p-10">
                @csrf
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" label="Nama" :value="auth()->user()?->name" required />
                    <x-form.input name="whatsapp" type="tel" label="WhatsApp" :value="auth()->user()?->whatsapp" placeholder="0812xxxxxxxx" required />
                    <x-form.input name="email" type="email" label="Email" :value="auth()->user()?->email" />
                    <x-form.input name="area" label="Area" />
                    <x-form.input name="church_connection" label="Current church connection" placeholder="Misal: rutin Sunday Service, LifeGroup X" class="sm:col-span-2" />
                </div>

                <fieldset>
                    <legend class="label">Interested ministry <span class="text-danger">*</span></legend>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($ministries as $ministry)
                            <label class="chip"><input type="checkbox" name="ministries[]" value="{{ $ministry->id }}" class="sr-only" @checked(in_array($ministry->id, old('ministries', [])))> {{ $ministry->name }}</label>
                        @endforeach
                    </div>
                    @error('ministries')<p class="error">{{ $message }}</p>@enderror
                </fieldset>

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
                        <input type="text" x-model="draft" x-on:keydown.enter.prevent="add()" x-on:keydown.comma.prevent="add()" x-on:blur="add()" placeholder="Ketik lalu Enter" class="min-w-40 grow border-0 p-0 text-sm focus:ring-0">
                    </div>
                </div>

                <x-form.textarea name="experience" label="Experience" rows="3" />

                <fieldset>
                    <legend class="label">Availability</legend>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($availability as $value => $label)
                            <label class="chip"><input type="checkbox" name="availability[]" value="{{ $value }}" class="sr-only" @checked(in_array($value, old('availability', [])))> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <x-form.textarea name="motivation" label="Why do you want to serve?" rows="4" required />

                <button class="btn btn-primary btn-lg w-full sm:w-auto">Submit application</button>
                <p class="text-xs text-muted">Coordinator ministry akan menghubungimu untuk ngobrol dan orientasi. Menjadi volunteer tidak otomatis memberi akses sistem.</p>
            </form>
        </div>
    </section>
</x-layouts.site>
