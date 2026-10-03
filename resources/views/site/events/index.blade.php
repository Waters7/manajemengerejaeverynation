<x-layouts.site title="Events" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Events', 'title' => "WHAT'S\nHAPPENING.", 'lead' => 'Ibadah, kelas, campus night, dan momen spesial untuk bertumbuh bersama.'])

    <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-wrap items-center gap-2">
                <a href="{{ route('events.index') }}" @class(['chip', '!border-brand !bg-brand !text-white' => ! request('category') && ! $past])>All upcoming</a>
                @foreach ($categories as $category)
                    <a href="{{ route('events.index', ['category' => $category->slug]) }}" @class(['chip', '!border-brand !bg-brand !text-white' => request('category') === $category->slug])>{{ $category->name }}</a>
                @endforeach
                <a href="{{ route('events.index', ['past' => 1]) }}" @class(['chip ml-auto', '!border-ink !bg-ink !text-white' => $past])>Past events</a>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($events as $event)
                    @include('site.partials.event-card', ['event' => $event])
                @empty
                    <div class="card md:col-span-2 lg:col-span-3"><x-empty title="Belum ada event" icon="calendar">Event baru akan segera diumumkan.</x-empty></div>
                @endforelse
            </div>
            <div class="mt-10">{{ $events->links() }}</div>
        </div>
    </section>
</x-layouts.site>
