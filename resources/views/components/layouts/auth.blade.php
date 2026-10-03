@props(['title' => 'Login', 'heading' => null, 'subheading' => null])
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
</head>
<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="duotone hidden flex-col justify-between p-12 text-white lg:flex">
            @if ($settings->get('hero_image'))
                <img src="{{ Storage::disk('public')->url($settings->get('hero_image')) }}" alt="">
            @endif
            <a href="{{ route('home') }}"><img src="{{ asset('images/logo-white.png') }}" alt="{{ $settings->get('site_name') }}" class="h-12 w-auto"></a>
            <div>
                <p class="display">HONOR GOD.<br>MAKE DISCIPLES.</p>
                <p class="mt-4 max-w-md text-lg text-white/80">Together, we follow Jesus and help others follow Him.</p>
            </div>
            <p class="text-xs text-white/60">© {{ now()->year }} {{ $settings->get('site_name') }}</p>
        </div>
        <div class="flex flex-col justify-center px-4 py-12 sm:px-12">
            <div class="mx-auto w-full max-w-md">
                <a href="{{ route('home') }}" class="lg:hidden"><img src="{{ asset('images/logo-blue.png') }}" alt="{{ $settings->get('site_name') }}" class="mb-10 h-11 w-auto"></a>
                @if ($heading)
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink uppercase">{{ $heading }}</h1>
                @endif
                @if ($subheading)
                    <p class="mt-2 text-sm text-muted">{{ $subheading }}</p>
                @endif
                <div class="mt-8">{{ $slot }}</div>
            </div>
        </div>
    </div>
    <x-flash />
</body>
</html>
