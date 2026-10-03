@props(['title' => null, 'description' => null, 'transparent' => false])
@php
    $nav = [
        ['Home', 'home', 'home'],
        ['About', 'about', 'about'],
        ['Discipleship', 'discipleship', 'discipleship'],
        ['LifeGroup', 'lifegroups.index', 'lifegroups.*'],
        ['Events', 'events.index', 'events.*'],
        ['Devotional', 'devotionals.index', 'devotionals.*'],
        ['Sermons', 'sermons.index', 'sermons.*'],
        ['Campus Ministry', 'campus', 'campus'],
        ['Gallery', 'gallery.index', 'gallery.*'],
    ];
    $siteName = $settings->get('site_name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.$siteName : $siteName.' — Honor God. Make Disciples.' }}</title>
    <meta name="description" content="{{ $description ?? 'Every Nation Bekasi — Honor God. Make Disciples. Together, we follow Jesus and help others follow Him.' }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $description ?? 'Honor God. Make Disciples.' }}">
    <meta property="og:image" content="{{ asset('images/logo-blue.png') }}">
    <meta name="theme-color" content="#0067B9">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to content</a>

    <header x-data="{ open: false, scrolled: false }" x-init="scrolled = window.scrollY > 10"
        x-on:scroll.window="scrolled = window.scrollY > 10"
        @class(['fixed inset-x-0 top-0 z-40 transition-colors duration-300'])
        :class="scrolled || open || {{ $transparent ? 'false' : 'true' }} ? 'bg-white/95 shadow-[0_1px_0_#E5E7EB] backdrop-blur' : 'bg-transparent'">
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ $siteName }}">
                @if ($transparent)
                    <img src="{{ asset('images/logo-white.png') }}" alt="{{ $siteName }}" class="h-10 w-auto" x-show="!(scrolled || open)">
                    <img src="{{ asset('images/logo-blue.png') }}" alt="" class="h-10 w-auto" x-show="scrolled || open" x-cloak>
                @else
                    <img src="{{ asset('images/logo-blue.png') }}" alt="{{ $siteName }}" class="h-10 w-auto">
                @endif
            </a>

            <nav class="hidden items-center gap-1 xl:flex" aria-label="Main">
                @foreach ($nav as [$label, $route, $pattern])
                    <a href="{{ route($route) }}"
                        :class="scrolled || {{ $transparent ? 'false' : 'true' }} ? '{{ request()->routeIs($pattern) ? 'text-brand' : 'text-slate-700 hover:text-brand' }}' : 'text-white/90 hover:text-white'"
                        class="rounded-full px-3 py-2 text-[0.8125rem] font-bold tracking-wide transition">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 xl:flex">
                <a href="{{ route('get-involved') }}"
                    :class="scrolled || {{ $transparent ? 'false' : 'true' }} ? 'btn-outline' : 'btn-outline-white'"
                    class="btn btn-sm">Get Involved</a>
                <a href="{{ route('lifegroups.index') }}" class="btn btn-primary btn-sm">Join a LifeGroup</a>
                @auth
                    <a href="{{ auth()->user()->canAccessAdmin() ? route('admin.dashboard') : route('member.dashboard') }}"
                        class="ml-1 inline-flex items-center gap-2 rounded-full py-1 pr-1 pl-3 text-sm font-bold"
                        :class="scrolled || {{ $transparent ? 'false' : 'true' }} ? 'text-ink hover:bg-slate-100' : 'text-white hover:bg-white/10'">
                        {{ auth()->user()->displayName() }}
                        <x-avatar :name="auth()->user()->name" size="size-8" />
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-2 text-sm font-bold"
                        :class="scrolled || {{ $transparent ? 'false' : 'true' }} ? 'text-slate-700 hover:text-brand' : 'text-white hover:text-white/80'">Login</a>
                @endauth
            </div>

            <button type="button" class="rounded-full p-2 xl:hidden" x-on:click="open = !open" aria-label="Toggle menu"
                :class="scrolled || open || {{ $transparent ? 'false' : 'true' }} ? 'text-ink' : 'text-white'">
                <x-icon name="menu" class="size-6" x-show="!open" />
                <x-icon name="x" class="size-6" x-show="open" x-cloak />
            </button>
        </div>

        <div x-show="open" x-cloak x-transition.origin.top class="max-h-[calc(100vh-4.5rem)] overflow-y-auto border-t border-line bg-white xl:hidden">
            <nav class="mx-auto grid max-w-7xl gap-1 px-4 py-4 sm:px-6">
                @foreach ($nav as [$label, $route, $pattern])
                    <a href="{{ route($route) }}" @class(['rounded-xl px-3 py-2.5 text-base font-bold', 'bg-brand-50 text-brand' => request()->routeIs($pattern), 'text-ink hover:bg-slate-50' => ! request()->routeIs($pattern)])>{{ $label }}</a>
                @endforeach
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('lifegroups.index') }}" class="btn btn-primary">Join a LifeGroup</a>
                    <a href="{{ route('get-involved') }}" class="btn btn-outline">Get Involved</a>
                </div>
                @auth
                    <a href="{{ route('member.dashboard') }}" class="mt-2 px-3 py-2 text-sm font-bold text-brand">My Dashboard →</a>
                @else
                    <a href="{{ route('login') }}" class="mt-2 px-3 py-2 text-sm font-bold text-brand">Login →</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="main" @class(['pt-18' => ! $transparent])>
        {{ $slot }}
    </main>

    <footer class="bg-ink text-white">
        <div class="mx-auto max-w-7xl px-4 pt-16 pb-10 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <img src="{{ asset('images/logo-white.png') }}" alt="{{ $siteName }}" class="h-14 w-auto">
                    <p class="mt-6 text-2xl leading-tight font-extrabold tracking-tight">HONOR GOD.<br>MAKE DISCIPLES.</p>
                    <p class="mt-3 max-w-sm text-sm text-white/60">Together, we follow Jesus and help others follow Him.</p>
                </div>
                <div class="grid gap-8 sm:grid-cols-3 lg:col-span-8">
                    <div>
                        <p class="text-xs font-bold tracking-[0.2em] text-white/50 uppercase">Visit us</p>
                        <p class="mt-4 text-sm font-semibold">{{ $settings->get('service_times') }}</p>
                        <p class="mt-2 text-sm whitespace-pre-line text-white/70">{{ $settings->get('address') }}</p>
                        @if ($settings->get('maps_url'))
                            <a href="{{ $settings->get('maps_url') }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-sm font-bold text-brand-300 hover:text-white"><x-icon name="map-pin" class="size-4" /> Get directions</a>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-[0.2em] text-white/50 uppercase">Next steps</p>
                        <ul class="mt-4 space-y-2 text-sm text-white/80">
                            <li><a href="{{ route('get-involved') }}" class="hover:text-white">Get Involved</a></li>
                            <li><a href="{{ route('lifegroups.index') }}" class="hover:text-white">Join a LifeGroup</a></li>
                            <li><a href="{{ route('get-involved.serve') }}" class="hover:text-white">Serve With Us</a></li>
                            <li><a href="{{ route('prayer.create') }}" class="hover:text-white">Prayer Request</a></li>
                            <li><a href="{{ route('connect') }}" class="hover:text-white">Connect Card</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-[0.2em] text-white/50 uppercase">Stay connected</p>
                        <ul class="mt-4 space-y-2 text-sm text-white/80">
                            @if ($settings->get('instagram_url'))<li><a href="{{ $settings->get('instagram_url') }}" target="_blank" rel="noopener" class="hover:text-white">Instagram</a></li>@endif
                            @if ($settings->get('youtube_url'))<li><a href="{{ $settings->get('youtube_url') }}" target="_blank" rel="noopener" class="hover:text-white">YouTube</a></li>@endif
                            @if ($settings->get('spotify_url'))<li><a href="{{ $settings->get('spotify_url') }}" target="_blank" rel="noopener" class="hover:text-white">Spotify</a></li>@endif
                            @if ($settings->get('contact_email'))<li><a href="mailto:{{ $settings->get('contact_email') }}" class="hover:text-white">{{ $settings->get('contact_email') }}</a></li>@endif
                            @if ($settings->get('contact_whatsapp'))<li><a href="{{ app(\App\Services\WhatsApp::class)->link($settings->get('contact_whatsapp')) }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a></li>@endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-14 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ now()->year }} {{ $siteName }}. Part of the Every Nation family of churches.</p>
                <p><a href="{{ route('login') }}" class="hover:text-white">Ministry login</a></p>
            </div>
        </div>
    </footer>

    <x-flash />
    @livewireScripts
</body>
</html>
