@props(['title' => null])
@php
    $user = auth()->user();
    $ws = $user->workspace();
    $menu = $ws === 'client' ? [
        ['Dashboard', 'dashboard', 'grid', ['dashboard']],
        ['My Jobs', 'client.jobs.index', 'briefcase', ['client.jobs.*']],
        ['Find Freelancers', 'freelancers.index', 'user', ['freelancers.*']],
        ['Saved Talent', 'client.saved', 'bookmark', ['client.saved']],
        ['Projects', 'projects.index', 'folder', ['projects.*']],
        ['Message', 'messages.index', 'mail', ['messages.*'], $navCounts['messages'] ?? 0],
        ['Notification', 'notifications.index', 'bell', ['notifications.*'], $navCounts['notifications'] ?? 0],
    ] : [
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
<body class="min-h-screen" x-data="{ nav: false, side: (() => { try { return localStorage.getItem('sidebar') === 'open' } catch (e) { return false } })() }"
      x-init="$watch('side', v => { try { localStorage.setItem('sidebar', v ? 'open' : 'closed') } catch (e) {} })">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="nav = false"></div>
    <aside :class="[nav ? 'translate-x-0' : '-translate-x-full', side ? 'lg:w-[280px]' : 'lg:w-[84px]']"
           class="fixed inset-y-0 left-0 z-40 w-[280px] -translate-x-full overflow-y-auto overflow-x-hidden bg-canvas transition-all duration-200 lg:sticky lg:top-0 lg:h-screen lg:w-[84px] lg:translate-x-0">

        {{-- Collapsed rail (desktop default): avatar first; clicking it expands the full sidebar --}}
        <div x-show="!side && !nav" class="hidden min-h-full flex-col items-center gap-1 px-3 pb-5 pt-6 lg:flex">
            <button type="button" @click="side = true" class="group relative rounded-full p-0.5 ring-2 ring-transparent transition hover:ring-ink/15" aria-label="Expand menu" title="{{ $user->name }} — open menu">
                <x-avatar :user="$user" size="size-12" class="text-sm"/>
                <span class="absolute -bottom-0.5 -right-0.5 grid size-5 place-items-center rounded-full bg-ink text-white ring-2 ring-canvas"><x-icon name="chevron-right" class="size-3"/></span>
            </button>
            <a href="{{ route('home') }}" class="mt-3 grid size-11 place-items-center rounded-xl bg-ink text-white shadow-[0_6px_14px_-6px_rgba(12,12,16,.6)] transition hover:scale-105" title="Discover" aria-label="{{ config('marketplace.brand') }} — Discover">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 7h10M7 12h7M7 17h10"/><circle cx="18" cy="12" r="1.6" fill="#34c759" stroke="none"/></svg>
            </a>
            <div class="my-4 h-px w-8 bg-zinc-200"></div>
            @foreach($menu as $item)
                <a href="{{ route($item[1]) }}" title="{{ $item[0] }}" aria-label="{{ $item[0] }}"
                   class="relative grid size-11 place-items-center rounded-xl transition {{ request()->routeIs(...$item[3]) ? 'border border-line bg-white text-ink shadow-sm' : 'text-muted hover:bg-white/70 hover:text-ink' }}">
                    <x-icon :name="$item[2]" class="size-5"/>
                    @if(($item[4] ?? 0) > 0)<span class="absolute right-1.5 top-1.5 size-2 rounded-full bg-leaf ring-2 ring-canvas"></span>@endif
                </a>
            @endforeach
            <div class="my-3 h-px w-8 bg-zinc-200"></div>
            @foreach(array_filter([['Settings', 'settings', 'settings', ['settings']], ['Billing', 'billing.index', 'dollar', ['billing.*', 'sandbox.*']], $user->isAdmin() ? ['Admin console', 'admin.overview', 'shield', ['admin.*']] : null, ['Help', 'how', 'help', ['how']]]) as $item)
                <a href="{{ route($item[1]) }}" title="{{ $item[0] }}" aria-label="{{ $item[0] }}"
                   class="grid size-11 place-items-center rounded-xl transition {{ request()->routeIs(...$item[3]) ? 'border border-line bg-white text-ink shadow-sm' : 'text-muted hover:bg-white/70 hover:text-ink' }}"><x-icon :name="$item[2]" class="size-5"/></a>
            @endforeach
        </div>

        {{-- Full sidebar --}}
        <div x-show="side || nav" x-cloak class="flex min-h-full w-[280px] flex-col px-5 pb-5 pt-7">
        <div class="flex items-center justify-between px-2">
            <x-logo/>
            <button class="btn-ghost rounded-lg p-1.5 lg:hidden" @click="nav = false" aria-label="Close menu"><x-icon name="x"/></button>
            <button class="hidden rounded-lg p-1.5 text-muted hover:bg-white hover:text-ink lg:block" @click="side = false" aria-label="Collapse menu" title="Collapse menu"><x-icon name="chevron-left"/></button>
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
        </div>
    </aside>

    {{-- Main --}}
    <div class="min-w-0 flex-1 p-3 sm:p-4 lg:pl-0">
        <div class="mb-3 flex items-center justify-between rounded-2xl bg-white px-4 py-3 lg:hidden">
            <button @click="nav = true" class="rounded-full" aria-label="Open menu"><x-avatar :user="$user" size="size-9" class="text-xs"/></button>
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
