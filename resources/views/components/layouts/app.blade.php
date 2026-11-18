@props(['title' => null])
@php
    $user = auth()->user();
    $ws = $user->workspace();
    $menu = $ws === 'client' ? [
        ['Discover', 'home', 'image', ['home']],
        ['Dashboard', 'dashboard', 'grid', ['dashboard']],
        ['My Jobs', 'client.jobs.index', 'briefcase', ['client.jobs.*']],
        ['Find Freelancers', 'freelancers.index', 'user', ['freelancers.*']],
        ['Saved Talent', 'client.saved', 'bookmark', ['client.saved']],
        ['Projects', 'projects.index', 'folder', ['projects.*']],
        ['Message', 'messages.index', 'mail', ['messages.*'], $navCounts['messages'] ?? 0],
        ['Notification', 'notifications.index', 'bell', ['notifications.*'], $navCounts['notifications'] ?? 0],
    ] : [
        ['Discover', 'home', 'image', ['home']],
        ['Dashboard', 'dashboard', 'grid', ['dashboard']],
        ['Find Project', 'jobs.index', 'search', ['jobs.index', 'jobs.show', 'applications.create']],
        ['My Portfolio', 'portfolio.index', 'layers', ['portfolio.*']],
        ['My Proposal', 'applications.mine', 'file', ['applications.mine', 'applications.show']],
        ['Invitations', 'invitations.index', 'sparkles', ['invitations.*']],
        ['Projects', 'projects.index', 'folder', ['projects.*']],
        ['Message', 'messages.index', 'mail', ['messages.*'], $navCounts['messages'] ?? 0],
        ['Notification', 'notifications.index', 'bell', ['notifications.*'], $navCounts['notifications'] ?? 0],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => $title])</head>
<body class="min-h-screen" x-data="{ nav: false }">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="nav = false"></div>
    <aside :class="nav ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-[280px] -translate-x-full flex-col overflow-y-auto bg-canvas px-5 pb-5 pt-7 transition lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="flex items-center justify-between px-2">
            <x-logo/>
            <button class="btn-ghost rounded-lg p-1.5 lg:hidden" @click="nav = false" aria-label="Close menu"><x-icon name="x"/></button>
        </div>
        <div class="my-6 h-px bg-zinc-200"></div>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl px-2 py-1.5 text-left hover:bg-white/70" :aria-expanded="open">
                <x-avatar :user="$user" size="size-11"/>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[15px] font-semibold">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-muted">{{ ucfirst($ws) }} workspace</span>
                </span>
                <x-icon name="chevron-down" class="size-4 text-ink-soft"/>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute inset-x-0 top-full z-50 mt-2 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                <p class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wider text-faint">Switch workspace</p>
                @foreach(['freelancer' => 'Freelancer', 'client' => 'Client'] as $key => $label)
                    @php $has = $key === 'freelancer' ? $user->is_freelancer : $user->is_client; @endphp
                    <form method="POST" action="{{ $has ? route('workspace.switch') : route('workspace.enable') }}">@csrf
                        <input type="hidden" name="{{ $has ? 'workspace' : 'role' }}" value="{{ $key }}">
                        <button class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-zinc-50">
                            <span>{{ $has ? $label : 'Enable '.$label }}</span>
                            @if($ws === $key)<x-icon name="check" class="size-4 text-leaf-dark"/>@endif
                        </button>
                    </form>
                @endforeach
                <div class="my-1 h-px bg-line"></div>
                @if($user->is_freelancer && $user->freelancerProfile)
                    <a href="{{ route('freelancers.show', $user->freelancerProfile) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="eye" class="size-4"/> View public profile</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4"/> Sign out</button>
                </form>
            </div>
        </div>
        <div class="my-6 h-px bg-zinc-200"></div>

        <p class="px-3 text-[11px] font-medium uppercase tracking-wider text-faint">Menu</p>
        <nav class="mt-3 space-y-1">
            @foreach($menu as $item)
                <a href="{{ route($item[1]) }}" class="nav-link {{ request()->routeIs(...$item[3]) ? 'active' : '' }}">
                    <x-icon :name="$item[2]" class="size-5"/>
                    <span class="flex-1">{{ $item[0] }}</span>
                    @if(($item[4] ?? 0) > 0)<span class="grid h-5 min-w-5 place-items-center rounded-full bg-leaf px-1.5 text-[11px] font-semibold text-white">{{ $item[4] }}</span>@endif
                </a>
            @endforeach
        </nav>

        <p class="mt-8 px-3 text-[11px] font-medium uppercase tracking-wider text-faint">Preferences</p>
        <nav class="mt-3 space-y-1">
            <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}"><x-icon name="settings"/> Settings</a>
            <a href="{{ route('billing.index') }}" class="nav-link {{ request()->routeIs('billing.*', 'sandbox.*') ? 'active' : '' }}"><x-icon name="dollar"/> Billing</a>
            @if($user->isAdmin())
                <a href="{{ route('admin.overview') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"><x-icon name="shield"/> Admin console</a>
            @endif
            <a href="{{ route('how') }}" class="nav-link"><x-icon name="help"/> Help</a>
        </nav>

        <div class="mt-auto pt-8">
            @if($ws === 'freelancer' && $user->freelancerProfile?->plan !== 'professional')
                <div class="relative overflow-hidden rounded-2xl border border-line bg-white p-5">
                    <div class="grid-lines pointer-events-none absolute -right-6 -top-6 size-36 opacity-70 [mask-image:radial-gradient(circle_at_top_right,black,transparent_70%)]"></div>
                    <span class="relative grid size-11 place-items-center rounded-xl bg-gradient-to-b from-[#5ee07a] to-[#22a845] text-white shadow-[0_6px_14px_-6px_rgba(34,168,69,.8)]"><x-icon name="rocket" class="size-5"/></span>
                    <p class="relative mt-4 font-display text-xl font-bold">Professional</p>
                    <p class="relative mt-1.5 text-xs leading-relaxed text-muted">More portfolio panels, a bigger media allowance and detailed analytics.</p>
                    <a href="{{ route('billing.index') }}" class="relative mt-4 inline-flex items-center gap-1.5 text-sm font-semibold underline underline-offset-4">Upgrade Now <x-icon name="arrow-right" class="size-4"/></a>
                </div>
            @elseif($ws === 'client')
                <div class="relative overflow-hidden rounded-2xl border border-line bg-white p-5">
                    <div class="grid-lines pointer-events-none absolute -right-6 -top-6 size-36 opacity-70 [mask-image:radial-gradient(circle_at_top_right,black,transparent_70%)]"></div>
                    <span class="relative grid size-11 place-items-center rounded-xl bg-gradient-to-b from-[#5b8cff] to-brand text-white"><x-icon name="megaphone" class="size-5"/></span>
                    <p class="relative mt-4 font-display text-xl font-bold">Post a job</p>
                    <p class="relative mt-1.5 text-xs leading-relaxed text-muted">Reach creative talent with structured screening questions.</p>
                    <a href="{{ route('client.jobs.create') }}" class="relative mt-4 inline-flex items-center gap-1.5 text-sm font-semibold underline underline-offset-4">Create job <x-icon name="arrow-right" class="size-4"/></a>
                </div>
            @endif
        </div>
    </aside>

    {{-- Main --}}
    <div class="min-w-0 flex-1 p-3 sm:p-4 lg:pl-0">
        <div class="mb-3 flex items-center justify-between rounded-2xl bg-white px-4 py-3 lg:hidden">
            <button @click="nav = true" class="rounded-lg p-1" aria-label="Open menu"><x-icon name="menu"/></button>
            <x-logo/>
            <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-1" aria-label="Notifications"><x-icon name="bell"/>@if(($navCounts['notifications'] ?? 0) > 0)<span class="absolute right-0 top-0 size-2 rounded-full bg-leaf"></span>@endif</a>
        </div>
        @if(! $user->hasVerifiedEmail())
            <div class="mb-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Please verify your email address. <a class="font-semibold underline" href="{{ route('verification.notice') }}">Verify now</a></div>
        @endif
        <main class="space-y-3 sm:space-y-4">{{ $slot }}</main>
    </div>
</div>
<x-flash/>
</body>
</html>
