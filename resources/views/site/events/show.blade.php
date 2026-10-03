<x-layouts.site :title="$event->title" :description="$event->excerpt">
    <section class="bg-canvas py-12 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-5 lg:px-8">
            <article class="lg:col-span-3">
                <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> All events</a>
                @if ($event->coverUrl())
                    <img src="{{ $event->coverUrl() }}" alt="" class="mt-6 aspect-[16/9] w-full rounded-[2rem] object-cover">
                @endif
                @if ($event->category)
                    <p class="mt-8 text-xs font-bold tracking-widest uppercase" style="color: {{ $event->category->color }}">{{ $event->category->name }}</p>
                @endif
                <h1 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $event->title }}</h1>
                @if ($event->excerpt)<p class="mt-4 text-lg text-muted">{{ $event->excerpt }}</p>@endif
                <div class="prose-church mt-8">{!! $event->description !!}</div>
            </article>

            <aside class="space-y-4 lg:col-span-2">
                <div class="card p-6">
                    <ul class="space-y-4 text-sm">
                        <li class="flex gap-3"><x-icon name="calendar" class="size-5 shrink-0 text-brand" />
                            <span><strong class="block text-ink">{{ $event->starts_at->translatedFormat('l, j F Y') }}</strong>
                                {{ $event->starts_at->format('H:i') }}@if ($event->ends_at) – {{ $event->ends_at->isSameDay($event->starts_at) ? $event->ends_at->format('H:i') : $event->ends_at->translatedFormat('j M Y H:i') }}@endif WIB</span></li>
                        @if ($event->location)
                            <li class="flex gap-3"><x-icon name="map-pin" class="size-5 shrink-0 text-brand" />
                                <span><strong class="block text-ink">{{ $event->location }}</strong>
                                    @if ($event->maps_url)<a href="{{ $event->maps_url }}" target="_blank" rel="noopener" class="font-semibold text-brand hover:underline">Open in Maps</a>@endif</span></li>
                        @endif
                        @if ($event->contact_person)
                            <li class="flex gap-3"><x-icon name="user" class="size-5 shrink-0 text-brand" />
                                <span><strong class="block text-ink">Contact person</strong>{{ $event->contact_person }}
                                    @if ($event->contact_whatsapp)· <a href="{{ app(\App\Services\WhatsApp::class)->link($event->contact_whatsapp, 'Halo, saya ingin bertanya tentang '.$event->title) }}" target="_blank" rel="noopener" class="font-semibold text-brand">WhatsApp</a>@endif</span></li>
                        @endif
                        @if ($event->capacity)
                            <li class="flex gap-3"><x-icon name="users" class="size-5 shrink-0 text-brand" />
                                <span><strong class="block text-ink">{{ $seatsLeft }} seats left</strong>of {{ $event->capacity }}</span></li>
                        @endif
                    </ul>
                </div>

                @if ($event->registration_enabled)
                    <div class="card p-6 sm:p-8">
                        @if ($event->registrationOpen())
                            <h2 class="text-xl font-extrabold uppercase">Register</h2>
                            @if ($seatsLeft === 0)
                                <p class="mt-1 text-sm text-amber-700">Kuota penuh — {{ $event->waiting_list_enabled ? 'kamu akan masuk waiting list.' : 'pendaftaran ditutup.' }}</p>
                            @endif
                            @if ($seatsLeft !== 0 || $event->waiting_list_enabled)
                                <form method="POST" action="{{ route('events.register', $event->slug) }}" class="mt-5 space-y-4">
                                    @csrf
                                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                                    <x-form.input name="name" label="Nama" :value="auth()->user()?->name" required />
                                    <x-form.input name="whatsapp" type="tel" label="WhatsApp" :value="auth()->user()?->whatsapp" placeholder="0812xxxxxxxx" required />
                                    <x-form.input name="email" type="email" label="Email" :value="auth()->user()?->email" />
                                    <button class="btn btn-primary btn-lg w-full">{{ $seatsLeft === 0 ? 'Join waiting list' : 'Register now' }}</button>
                                </form>
                            @endif
                        @else
                            <p class="font-bold">Registration closed</p>
                            <p class="mt-1 text-sm text-muted">Pendaftaran untuk event ini sudah ditutup.</p>
                        @endif
                    </div>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.site>
