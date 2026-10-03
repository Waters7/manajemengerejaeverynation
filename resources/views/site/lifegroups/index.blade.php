<x-layouts.site title="LifeGroup" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'LifeGroup', 'title' => "FIND YOUR\nPEOPLE.", 'lead' => 'Komunitas kecil untuk bertumbuh dalam Firman, berdoa bersama, dan menjalani hidup sebagai keluarga.'])

    <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="card mb-10 grid gap-3 p-4 sm:grid-cols-4 sm:items-end">
                <x-form.select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All categories" />
                <x-form.select name="day" label="Day" :options="$days" :value="request('day')" placeholder="Any day" />
                <x-form.select name="area" label="Area" :options="$areas->combine($areas)->all()" :value="request('area')" placeholder="All areas" />
                <div class="flex gap-2">
                    <button class="btn btn-primary grow">Find</button>
                    @if (request()->query())<a href="{{ route('lifegroups.index') }}" class="btn btn-outline">Reset</a>@endif
                </div>
            </form>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($groups as $group)
                    @include('site.partials.lifegroup-card', ['group' => $group])
                @empty
                    <div class="card sm:col-span-2 lg:col-span-3">
                        <x-empty title="Belum ada LifeGroup yang cocok" icon="users">
                            Coba ubah filter, atau <a href="{{ route('get-involved') }}" class="font-bold text-brand">isi form Get Involved</a> dan kami akan membantu mencarikan LifeGroup untukmu.
                        </x-empty>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.site>
