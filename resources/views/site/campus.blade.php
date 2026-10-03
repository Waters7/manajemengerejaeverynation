<x-layouts.site title="Campus Ministry" :transparent="true">
    @include('site.partials.page-hero', [
        'eyebrow' => 'Campus Ministry',
        'title' => $settings->get('campus_headline'),
        'lead' => $settings->get('campus_body'),
        'image' => $settings->get('campus_image') ? Storage::disk('public')->url($settings->get('campus_image')) : null,
    ])

    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Reach', 'Menjangkau mahasiswa dengan kabar baik melalui relasi dan event kampus.', 'globe'],
                    ['Disciple', 'Memuridkan mahasiswa melalui One 2 One dan Campus LifeGroup.', 'book'],
                    ['Send', 'Memperlengkapi mahasiswa menjadi pemimpin yang mengubah dunia.', 'trend'],
                ] as [$heading, $text, $icon])
                    <div class="card card-pad">
                        <x-icon :name="$icon" class="size-8 text-brand" />
                        <h3 class="mt-4 text-xl font-extrabold uppercase">{{ $heading }}</h3>
                        <p class="mt-2 text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            @if ($campuses->isNotEmpty())
                <h2 class="heading mt-20">Where we are</h2>
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($campuses as $campus)
                        <span class="chip !cursor-default"><x-icon name="campus" class="size-4 text-brand" /> {{ $campus->name }}</span>
                    @endforeach
                </div>
            @endif

            @if ($lifeGroups->isNotEmpty())
                <h2 class="heading mt-20">Campus LifeGroups</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($lifeGroups as $group)
                        @include('site.partials.lifegroup-card', ['group' => $group])
                    @endforeach
                </div>
            @endif

            @if ($events->isNotEmpty())
                <h2 class="heading mt-20">Campus events</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($events as $event)
                        @include('site.partials.event-card', ['event' => $event])
                    @endforeach
                </div>
            @endif

            <div class="mt-20 rounded-[2rem] bg-ink p-10 text-white sm:p-14">
                <h2 class="heading">Mahasiswa? Let's connect!</h2>
                <p class="mt-3 max-w-xl text-white/75">Daftar dan pilih “Mengikuti Campus Ministry” — tim kampus kami akan menghubungimu.</p>
                <a href="{{ route('get-involved') }}" class="btn btn-primary btn-lg mt-8">Get Involved</a>
            </div>
        </div>
    </section>
</x-layouts.site>
