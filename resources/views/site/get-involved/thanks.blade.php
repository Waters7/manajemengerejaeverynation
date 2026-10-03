<x-layouts.site title="Thank you">
    <section class="bg-canvas py-20 sm:py-28">
        <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
            <span class="mx-auto grid size-20 place-items-center rounded-full bg-brand text-white shadow-[var(--shadow-lift)]"><x-icon name="heart" class="size-9" /></span>
            <h1 class="heading mt-8">Thank you{{ $nickname ? ', '.$nickname : '' }}!</h1>
            <p class="prose-church mt-4">{{ $settings->get('get_involved_thanks') }}</p>
            <div class="mt-10 grid gap-4 text-left sm:grid-cols-3">
                <a href="{{ route('lifegroups.index') }}" class="card card-hover card-pad"><x-icon name="users" class="size-6 text-brand" /><p class="mt-3 font-extrabold">Find a LifeGroup</p></a>
                <a href="{{ route('events.index') }}" class="card card-hover card-pad"><x-icon name="calendar" class="size-6 text-brand" /><p class="mt-3 font-extrabold">Upcoming events</p></a>
                <a href="{{ route('devotionals.index') }}" class="card card-hover card-pad"><x-icon name="book" class="size-6 text-brand" /><p class="mt-3 font-extrabold">Read a devotional</p></a>
            </div>
            @guest
                <p class="mt-10 text-sm text-muted">Ingin melihat perjalananmu? <a href="{{ route('register') }}" class="font-bold text-brand">Buat akun</a> dengan nomor WhatsApp yang sama.</p>
            @endguest
        </div>
    </section>
</x-layouts.site>
