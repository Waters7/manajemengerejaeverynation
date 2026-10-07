@props(['title' => 'Dashboard'])
@php
    $user = auth()->user();
    /** Sidebar: [section => [[label, route, pattern, icon, permission]]] */
    $sections = [
        '' => [
            ['Dashboard', 'admin.dashboard', 'admin.dashboard', 'home', 'admin.access'],
        ],
        'People' => [
            ['Members', 'admin.members.index', 'admin.members.*', 'users', 'members.view'],
            ['Newcomers', 'admin.newcomers.index', 'admin.newcomers.*', 'user-plus', 'newcomers.view'],
            ['Get Involved', 'admin.involvement.index', 'admin.involvement.*', 'hand', 'involvement.view'],
            ['Follow-Ups', 'admin.follow-ups.index', 'admin.follow-ups.*', 'clipboard', 'followups.view'],
        ],
        'Discipleship' => [
            ['Journey', 'admin.journey.index', 'admin.journey.*', 'sprout', 'discipleship.view'],
            ['One 2 One', 'admin.one2one.index', 'admin.one2one.*', 'chat', 'discipleship.view'],
            ['Curriculum', 'admin.curriculum.index', 'admin.curriculum.*', 'list', 'curriculum.manage'],
            ['Books', 'admin.books.index', 'admin.books.*', 'book', 'curriculum.manage'],
            ['Classes', 'admin.classes.index', 'admin.classes.*', 'academic', 'classes.view'],
            ['Victory Weekend', 'admin.victory-weekend.index', 'admin.victory-weekend.*', 'star', 'classes.view'],
            ['Disciplers', 'admin.disciplers.index', 'admin.disciplers.*', 'tree', 'discipleship.view'],
            ['Leadership Pipeline', 'admin.leadership.index', 'admin.leadership.*', 'trend', 'leadership.view'],
        ],
        'Community' => [
            ['LifeGroups', 'admin.lifegroups.index', 'admin.lifegroups.*', 'users', 'lifegroups.view'],
            ['Join Requests', 'admin.join-requests.index', 'admin.join-requests.*', 'inbox', 'lifegroups.requests'],
            ['Meetings & Attendance', 'admin.meetings.index', 'admin.meetings.*', 'calendar', 'lifegroups.view'],
        ],
        'Ministry' => [
            ['Ministries', 'admin.ministries.index', 'admin.ministries.*', 'sparkles', 'ministries.view'],
            ['Volunteers', 'admin.volunteers.index', 'admin.volunteers.*', 'hand', 'ministries.view'],
            ['Applications', 'admin.volunteer-applications.index', 'admin.volunteer-applications.*', 'document', 'volunteers.manage'],
            ['Serving Schedule', 'admin.serving.index', 'admin.serving.*', 'calendar', 'ministries.view'],
        ],
        'Campus' => [
            ['Campus Ministry', 'admin.campuses.index', 'admin.campuses.*', 'campus', 'campus.view'],
        ],
        'Content' => [
            ['Homepage', 'admin.homepage.edit', 'admin.homepage.*', 'home', 'content.manage'],
            ['Devotionals', 'admin.devotionals.index', 'admin.devotionals.*', 'book', 'content.manage'],
            ['Sermons', 'admin.sermons.index', 'admin.sermons.*', 'play', 'content.manage'],
            ['Events', 'admin.events.index', 'admin.events.*', 'calendar', 'events.manage'],
            ['Gallery', 'admin.galleries.index', 'admin.galleries.*', 'photo', 'content.manage'],
            ['Pages', 'admin.pages.index', 'admin.pages.*', 'document', 'content.manage'],
        ],
        'Store' => [
            ['Orders', 'admin.orders.index', 'admin.orders.*', 'cart', 'orders.manage'],
            ['Products', 'admin.store.products.index', 'admin.store.products.*', 'bag', 'store.manage'],
            ['Categories', 'admin.store.categories.index', 'admin.store.categories.*', 'tag', 'store.manage'],
            ['Store Settings', 'admin.store.settings.edit', 'admin.store.settings.*', 'cog', 'store.manage'],
        ],
        'Care' => [
            ['Prayer Requests', 'admin.prayer-requests.index', 'admin.prayer-requests.*', 'pray', 'prayer.view'],
            ['Pastoral Care', 'admin.pastoral-care.index', 'admin.pastoral-care.*', 'heart', 'pastoral.view'],
        ],
        'Communication' => [
            ['Announcements', 'admin.announcements.index', 'admin.announcements.*', 'megaphone', 'announcements.manage'],
            ['Birthdays', 'admin.birthdays.index', 'admin.birthdays.*', 'cake', 'birthdays.view'],
        ],
        'Reports' => [
            ['Reports', 'admin.reports.index', 'admin.reports.*', 'chart', 'reports.view'],
        ],
        'System' => [
            ['Users', 'admin.users.index', 'admin.users.*', 'user', 'users.manage'],
            ['Roles & Permissions', 'admin.roles.index', 'admin.roles.*', 'key', 'roles.manage'],
            ['Interests', 'admin.interests.index', 'admin.interests.*', 'heart', 'settings.manage'],
            ['Media', 'admin.media.index', 'admin.media.*', 'photo', 'media.manage'],
            ['Settings', 'admin.settings.edit', 'admin.settings.*', 'cog', 'settings.manage'],
            ['Audit Logs', 'admin.audit-logs.index', 'admin.audit-logs.*', 'shield', 'audit.view'],
        ],
    ];
    $unread = $user->unreadNotifications()->count();
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
<body class="min-h-screen bg-canvas" x-data="{ sidebar: false }">
    {{-- Sidebar --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink/40 lg:hidden" x-on:click="sidebar = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-line bg-white transition-transform lg:translate-x-0"
        :class="sidebar && '!translate-x-0'">
        <div class="flex h-16 shrink-0 items-center justify-between px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/mark-blue.png') }}" alt="" class="h-7 w-auto">
                <span class="leading-none">
                    <span class="block text-[0.8rem] font-extrabold tracking-wide text-ink">EVERY NATION</span>
                    <span class="block text-[0.6rem] font-bold tracking-[0.45em] text-brand">BEKASI</span>
                </span>
            </a>
            <button class="rounded-full p-1 lg:hidden" x-on:click="sidebar = false"><x-icon name="x" class="size-5" /></button>
        </div>
        <nav class="grow overflow-y-auto px-3 pb-6" aria-label="Admin">
            @foreach ($sections as $section => $items)
                @php $visible = array_filter($items, fn ($item) => $user->can($item[4])); @endphp
                @if ($visible)
                    @if ($section)<p class="nav-section">{{ $section }}</p>@endif
                    <div class="space-y-0.5">
                        @foreach ($visible as [$label, $route, $pattern, $icon])
                            <a href="{{ route($route) }}" @class(['nav-link', 'active' => request()->routeIs($pattern)])>
                                <x-icon :name="$icon" class="size-[1.15rem]" /> {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="border-t border-line p-3">
            <a href="{{ route('member.dashboard') }}" class="nav-link"><x-icon name="user" class="size-[1.15rem]" /> My personal dashboard</a>
            <a href="{{ route('home') }}" class="nav-link" target="_blank"><x-icon name="globe" class="size-[1.15rem]" /> View website</a>
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button class="rounded-full p-2 lg:hidden" x-on:click="sidebar = true" aria-label="Open menu"><x-icon name="menu" class="size-6" /></button>
            <form action="{{ route('admin.search') }}" method="GET" class="relative hidden max-w-md grow sm:block">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search people, LifeGroups, events…" class="input rounded-full border-transparent bg-slate-100 py-2 pl-9 focus:bg-white">
            </form>
            <div class="ml-auto flex items-center gap-1">
                <a href="{{ route('admin.notifications.index') }}" class="relative rounded-full p-2 text-slate-600 hover:bg-slate-100" aria-label="Notifications">
                    <x-icon name="bell" class="size-5" />
                    @if ($unread)
                        <span class="absolute top-1 right-1 grid min-w-4 place-items-center rounded-full bg-danger px-1 text-[0.6rem] font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button x-on:click="open = !open" x-on:click.outside="open = false" class="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 hover:bg-slate-100">
                        <x-avatar :profile="$user->profile" :name="$user->name" size="size-8" />
                        <span class="hidden text-left sm:block">
                            <span class="block text-sm leading-tight font-bold text-ink">{{ $user->displayName() }}</span>
                            <span class="block text-[0.7rem] leading-tight text-muted">{{ $user->primaryRole()?->label() }}</span>
                        </span>
                        <x-icon name="chevron-down" class="size-4 text-slate-400" />
                    </button>
                    <div x-show="open" x-cloak x-transition class="card absolute right-0 mt-2 w-56 p-1.5">
                        <a href="{{ route('member.profile.edit') }}" class="nav-link"><x-icon name="user" class="size-4" /> My profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="nav-link w-full"><x-icon name="logout" class="size-4" /> Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>

    <x-flash />
    @livewireScripts
</body>
</html>
