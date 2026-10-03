<x-layouts.site :title="$group->name">
    <section class="bg-canvas py-12 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-5 lg:px-8">
            <div class="lg:col-span-3">
                <a href="{{ route('lifegroups.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><x-icon name="arrow-left" class="size-4" /> All LifeGroups</a>
                <div class="mt-6 overflow-hidden rounded-[2rem]">
                    @if ($group->coverUrl())
                        <img src="{{ $group->coverUrl() }}" alt="" class="aspect-[16/9] w-full object-cover">
                    @else
                        <div class="flex aspect-[16/9] items-end bg-gradient-to-br from-brand to-brand-800 p-8"><x-icon name="users" class="size-16 text-white/30" /></div>
                    @endif
                </div>
                <div class="mt-8 flex flex-wrap items-center gap-2">
                    <x-badge :value="$group->category" color="blue" />
                    @if ($group->accepting_members)<x-badge color="green">Accepting members</x-badge>@else<x-badge color="gray">Currently full</x-badge>@endif
                </div>
                <h1 class="mt-3 text-4xl font-extrabold tracking-tight">{{ $group->name }}</h1>
                <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['Leader', $group->leader?->displayName().($group->coLeader ? ' & '.$group->coLeader->displayName() : ''), 'user'],
                        ['Schedule', $group->scheduleLabel() ?: 'TBA', 'clock'],
                        ['Area', $group->area ?: '—', 'map-pin'],
                    ] as [$term, $detail, $icon])
                        <div class="card p-4">
                            <dt class="flex items-center gap-1.5 text-xs font-bold tracking-wider text-muted uppercase"><x-icon :name="$icon" class="size-4" /> {{ $term }}</dt>
                            <dd class="mt-1 font-bold">{{ $detail }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($group->description)
                    <p class="prose-church mt-8 whitespace-pre-line">{{ $group->description }}</p>
                @endif
            </div>

            <aside class="lg:col-span-2">
                <div class="card sticky top-24 p-6 sm:p-8">
                    @if (session('joined'))
                        <div class="text-center">
                            <span class="mx-auto grid size-14 place-items-center rounded-full bg-green-50 text-success"><x-icon name="check" class="size-7" /></span>
                            <h2 class="mt-4 text-xl font-extrabold">Request sent!</h2>
                            <p class="mt-2 text-sm text-muted">Terima kasih sudah tertarik bergabung. Leader LifeGroup akan menghubungimu melalui WhatsApp dalam beberapa hari.</p>
                        </div>
                    @elseif ($group->accepting_members)
                        <h2 class="text-xl font-extrabold uppercase">Join this LifeGroup</h2>
                        <p class="mt-1 text-sm text-muted">Isi data singkat di bawah, leader akan menghubungimu.</p>
                        <form method="POST" action="{{ route('lifegroups.join', $group->slug) }}" class="mt-6 space-y-4">
                            @csrf
                            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                            <x-form.input name="name" label="Nama" :value="auth()->user()?->name" required />
                            <x-form.input name="whatsapp" type="tel" label="WhatsApp" placeholder="0812xxxxxxxx" :value="auth()->user()?->whatsapp" required />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-form.input name="email" type="email" label="Email" :value="auth()->user()?->email" />
                                <x-form.input name="age" type="number" label="Usia" min="10" max="100" />
                            </div>
                            <x-form.input name="area" label="Area tempat tinggal" />
                            <x-form.textarea name="notes" label="Catatan" rows="3" placeholder="Ceritakan sedikit tentang dirimu (opsional)" />
                            <button class="btn btn-primary btn-lg w-full">Join this LifeGroup</button>
                        </form>
                    @else
                        <h2 class="text-xl font-extrabold">This group is currently full</h2>
                        <p class="mt-2 text-sm text-muted">Lihat LifeGroup lain atau isi form Get Involved, kami akan membantu mencarikan yang cocok.</p>
                        <a href="{{ route('get-involved') }}" class="btn btn-primary mt-5 w-full">Get Involved</a>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    @if ($others->isNotEmpty())
        <section class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="heading">Other LifeGroups</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($others as $other)
                        @include('site.partials.lifegroup-card', ['group' => $other])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
