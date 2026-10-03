<x-layouts.site title="Discipleship" :transparent="true">
    @include('site.partials.page-hero', ['eyebrow' => 'Discipleship', 'title' => "ENGAGE. ESTABLISH.\nEQUIP. EMPOWER.", 'lead' => 'Pemuridan bukan sekadar program — ini adalah perjalanan seumur hidup mengikut Yesus bersama orang lain.'])

    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if ($page && $page->body)
                <div class="prose-church mb-16">{!! $page->body !!}</div>
            @endif
            <ol class="relative space-y-8 border-l-2 border-brand-100 pl-8 sm:pl-12">
                @foreach ($stages as $stage)
                    <li class="relative">
                        <span class="absolute top-1 -left-[2.65rem] grid size-10 place-items-center rounded-full text-sm font-extrabold text-white ring-8 ring-white sm:-left-[3.65rem]" style="background: {{ $stage->color }}">{{ $loop->iteration }}</span>
                        <div class="card card-pad">
                            <h2 class="text-2xl font-extrabold tracking-tight uppercase">{{ $stage->name }}</h2>
                            <p class="mt-1 font-semibold text-muted">{{ $stage->tagline }}</p>
                            @if ($stage->description)<p class="prose-church mt-3">{{ $stage->description }}</p>@endif
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                @foreach ($stage->activePrograms as $program)
                                    <div class="rounded-2xl border border-line p-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-extrabold">{{ $program->name }}</p>
                                            <x-badge :value="$program->type" />
                                        </div>
                                        @if ($program->description)<p class="mt-1 text-sm text-muted">{{ $program->description }}</p>@endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-16 rounded-[2rem] bg-brand p-10 text-white sm:p-14">
                <h2 class="heading">Ready to take a step?</h2>
                <p class="mt-3 max-w-xl text-white/85">Mulailah dengan One 2 One bersama seorang discipler. Kami akan menghubungkanmu dengan orang yang tepat.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('get-involved') }}" class="btn btn-white btn-lg">Start One 2 One</a>
                    <a href="{{ route('lifegroups.index') }}" class="btn btn-outline-white btn-lg">Join a LifeGroup</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
