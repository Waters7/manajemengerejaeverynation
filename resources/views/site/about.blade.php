<x-layouts.site title="About" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'About Every Nation Bekasi', 'title' => "HONOR GOD.\nMAKE DISCIPLES.", 'lead' => 'Together, we follow Jesus and help others follow Him.', 'image' => $page?->coverUrl()])

    <section class="py-20 sm:py-24">
        <div class="mx-auto grid max-w-7xl gap-14 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="lg:col-span-2">
                @if ($page && $page->body)
                    <div class="prose-church">{!! $page->body !!}</div>
                @else
                    <p class="eyebrow">Who we are</p>
                    <h2 class="heading mt-3">{{ $settings->get('about_heading') }}</h2>
                    <p class="prose-church mt-6">{{ $settings->get('about_body') }}</p>
                @endif
            </div>
            <aside class="space-y-4">
                <div class="card card-pad">
                    <p class="eyebrow">Sunday Service</p>
                    <p class="mt-2 text-lg font-extrabold">{{ $settings->get('service_times') }}</p>
                    <p class="mt-2 text-sm whitespace-pre-line text-muted">{{ $settings->get('address') }}</p>
                    @if ($settings->get('maps_url'))
                        <a href="{{ $settings->get('maps_url') }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm mt-4"><x-icon name="map-pin" class="size-4" /> Directions</a>
                    @endif
                </div>
                <div class="card card-pad bg-soft">
                    <p class="font-extrabold">First time visiting?</p>
                    <p class="mt-1 text-sm text-muted">Isi Connect Card supaya kami bisa menyambutmu.</p>
                    <a href="{{ route('connect') }}" class="btn btn-primary btn-sm mt-4">Connect Card</a>
                </div>
            </aside>
        </div>
    </section>

    <section class="bg-canvas py-20">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 md:grid-cols-3 lg:px-8">
            @foreach ([
                ['Honor God', 'Kami menghormati Tuhan dengan hidup yang menyembah, taat, dan berpusat pada Kristus.', 'heart'],
                ['Make Disciples', 'Kami menolong setiap orang mengikut Yesus melalui relasi, LifeGroup, dan pemuridan.', 'users'],
                ['Every Nation', 'Kami rindu menjangkau kampus, kota, dan bangsa-bangsa dengan kabar baik.', 'globe'],
            ] as [$heading, $text, $icon])
                <div class="card card-pad">
                    <span class="grid size-12 place-items-center rounded-2xl bg-brand text-white"><x-icon :name="$icon" class="size-6" /></span>
                    <h3 class="mt-5 text-xl font-extrabold uppercase">{{ $heading }}</h3>
                    <p class="mt-2 text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.site>
