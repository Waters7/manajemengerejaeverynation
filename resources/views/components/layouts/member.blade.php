@props(['title' => 'My Dashboard'])
@php
    $user = auth()->user();
    $links = [
        ['Dashboard', 'member.dashboard', 'home'],
        ['My Journey', 'member.journey', 'sprout'],
        ['My LifeGroup', 'member.lifegroup', 'users'],
        ['My Classes', 'member.classes', 'academic'],
        ['My Events', 'member.events', 'calendar'],
        ['Serving', 'member.serving', 'hand'],
        ['Certificates', 'member.certificates', 'star'],
        ['Prophetic Words', 'member.prophetic-words', 'music'],
        ['My Orders', 'member.orders', 'bag'],
    ];
    if ($user->profile?->discipleRelationships()->where('status', 'active')->exists()) {
        $links[] = ['My Disciples', 'member.disciples', 'tree'];
    }
    $links[] = ['Profile', 'member.profile.edit', 'user'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ $settings->get('site_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas">
    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur" x-data="{ open: false }">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('home') }}"><img src="{{ asset('images/logo-blue.png') }}" alt="{{ $settings->get('site_name') }}" class="h-9 w-auto"></a>
            <div class="flex items-center gap-2">
                @if ($user->canAccessAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm hidden sm:inline-flex"><x-icon name="shield" class="size-4" /> Ministry Dashboard</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost btn-sm"><x-icon name="logout" class="size-4" /> <span class="hidden sm:inline">Logout</span></button>
                </form>
                <button type="button" class="rounded-full p-2 md:hidden" x-on:click="open = !open" aria-label="Menu"><x-icon name="menu" class="size-6" /></button>
            </div>
        </div>
        <nav class="mx-auto hidden max-w-6xl gap-1 overflow-x-auto px-4 pb-2 sm:px-6 md:flex" :class="open && '!flex flex-col md:flex-row'">
            @foreach ($links as [$label, $route, $icon])
                <a href="{{ route($route) }}" @class(['inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-bold transition', 'bg-brand text-white' => request()->routeIs($route), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs($route)])>
                    <x-icon :name="$icon" class="size-4" /> {{ $label }}
                </a>
            @endforeach
        </nav>
    </header>

    @if ($user->account_status === \App\Enums\AccountStatus::PendingVerification)
        <div class="border-b border-amber-200 bg-amber-50">
            <p class="mx-auto max-w-6xl px-4 py-2.5 text-sm text-amber-800 sm:px-6">
                <strong>Welcome!</strong> Akun kamu sedang menunggu verifikasi dari tim kami. Sementara itu, kamu sudah bisa melihat journey, mendaftar event, dan bergabung dengan LifeGroup.
            </p>
        </div>
    @endif

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        {{ $slot }}
    </main>

    <x-flash />
    @livewireScripts
</body>
</html>
