@props(['title' => null])
@php
    $user = auth()->user();
    $ws = $user->workspace();
    $workspace = $ws === 'client' ? [
        ['Dashboard', 'dashboard', 'grid'], ['My Jobs', 'client.jobs.index', 'briefcase'], ['Post a job', 'client.jobs.create', 'plus'],
        ['Saved Talent', 'client.saved', 'bookmark'], ['Projects', 'projects.index', 'folder'],
    ] : [
        ['Dashboard', 'dashboard', 'grid'], ['My Portfolio', 'portfolio.index', 'layers'], ['My Proposals', 'applications.mine', 'file'],
        ['Invitations', 'invitations.index', 'sparkles'], ['Saved jobs', 'jobs.saved', 'bookmark'], ['Projects', 'projects.index', 'folder'],
    ];
    $primary = [['Explore', 'home', ['home']], ['Jobs', 'jobs.index', ['jobs.*']], ['Talent', 'freelancers.index', ['freelancers.*']]];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => $title])</head>
<body class="min-h-screen bg-white pb-10" x-data="{ mobile: false }">

{{-- Top bar --}}
<header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center gap-2 px-4 sm:px-6">
        <x-logo class="shrink-0"/>
        <nav class="ml-6 hidden h-16 items-stretch gap-1 md:flex" aria-label="Primary">
            @foreach($primary as [$label, $route, $active])
                <a href="{{ route($route) }}" class="flex items-center border-b-2 px-3 text-[15px] font-medium {{ request()->routeIs(...$active) ? 'border-ink text-ink' : 'border-transparent text-ink-soft hover:text-ink' }}">{{ $label }}</a>
            @endforeach
            <div x-data="{ open: false }" class="relative flex items-center" @keydown.escape="open = false">
                <button @click="open = !open" :aria-expanded="open" class="flex items-center gap-1 px-3 text-[15px] font-medium text-ink-soft hover:text-ink">My workspace <x-icon name="chevron-down" class="size-4"/></button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute left-0 top-full mt-1 w-60 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                    <p class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wider text-faint">{{ ucfirst($ws) }} workspace</p>
                    @foreach($workspace as [$label, $route, $icon])
                        <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon :name="$icon" class="size-4 text-muted"/>{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </nav>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            @if($ws === 'client')
                <a href="{{ route('client.jobs.create') }}" class="btn btn-light btn-pill hidden h-10 sm:inline-flex">Post a Job</a>
            @elseif($user->freelancerProfile)
                <a href="{{ route('portfolio.create') }}" class="btn btn-light btn-pill hidden h-10 sm:inline-flex">Share Work</a>
            @endif
            <a href="{{ route('messages.index') }}" class="relative grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Messages{{ ($navCounts['messages'] ?? 0) ? ' ('.$navCounts['messages'].' unread)' : '' }}">
                <x-icon name="mail" class="size-[22px]"/>@if($navCounts['messages'] ?? 0)<span class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-leaf ring-2 ring-white"></span>@endif
            </a>
            <a href="{{ route('notifications.index') }}" class="relative grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Notifications{{ ($navCounts['notifications'] ?? 0) ? ' ('.$navCounts['notifications'].' unread)' : '' }}">
                <x-icon name="bell" class="size-[22px]"/>@if($navCounts['notifications'] ?? 0)<span class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-leaf ring-2 ring-white"></span>@endif
            </a>

            {{-- Avatar menu --}}
            <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                <button @click="open = !open" :aria-expanded="open" class="rounded-full ring-2 ring-transparent transition hover:ring-ink/15" aria-label="Account menu">
                    <x-avatar :user="$user" size="size-9" class="text-xs"/>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 top-full mt-2 w-72 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                    <div class="flex items-center gap-3 px-3 py-3">
                        <x-avatar :user="$user" size="size-12" class="text-sm"/>
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $user->name }}</p><p class="truncate text-xs text-muted">{{ $user->email }}</p></div>
                    </div>
                    <div class="my-1 h-px bg-line"></div>
                    {{-- workspace links (mobile shows them here; desktop also has the "My workspace" menu) --}}
                    <div class="md:hidden">
                        @foreach([['Jobs', 'jobs.index', 'briefcase'], ['Talent', 'freelancers.index', 'users']] as [$label, $route, $icon])
                            <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon :name="$icon" class="size-4 text-muted"/>{{ $label }}</a>
                        @endforeach
                        @foreach($workspace as [$label, $route, $icon])
                            <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon :name="$icon" class="size-4 text-muted"/>{{ $label }}</a>
                        @endforeach
                        <div class="my-1 h-px bg-line"></div>
                    </div>
                    @if($user->is_freelancer && $user->freelancerProfile)
                        <a href="{{ route('freelancers.show', $user->freelancerProfile) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="user" class="size-4 text-muted"/> View public profile</a>
                    @endif
                    <a href="{{ route('settings') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="settings" class="size-4 text-muted"/> Settings</a>
                    <a href="{{ route('billing.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="dollar" class="size-4 text-muted"/> Billing</a>
                    @if($user->isAdmin())
                        <a href="{{ route('admin.overview') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="shield" class="size-4 text-muted"/> Admin console</a>
                    @endif
                    <div class="my-1 h-px bg-line"></div>
                    <p class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wider text-faint">Switch workspace</p>
                    @foreach(['freelancer' => 'Freelancer', 'client' => 'Client'] as $key => $label)
                        @php $has = $key === 'freelancer' ? $user->is_freelancer : $user->is_client; @endphp
                        <form method="POST" action="{{ $has ? route('workspace.switch') : route('workspace.enable') }}">@csrf
                            <input type="hidden" name="{{ $has ? 'workspace' : 'role' }}" value="{{ $key }}">
                            <button class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><span>{{ $has ? $label : 'Enable '.$label }}</span>@if($ws === $key)<x-icon name="check" class="size-4 text-leaf-dark"/>@endif</button>
                        </form>
                    @endforeach
                    <div class="my-1 h-px bg-line"></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4"/> Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

@if(! $user->hasVerifiedEmail())
    <div class="bg-amber-50 px-6 py-2.5 text-center text-sm text-amber-800">Please verify your email address. <a class="font-semibold underline" href="{{ route('verification.notice') }}">Verify now</a></div>
@endif

<main>{{ $slot }}</main>

{{-- Slim fixed footer, Behance-style --}}
<footer class="fixed inset-x-0 bottom-0 z-30 hidden border-t border-line bg-white/95 backdrop-blur md:block">
    <div class="flex h-10 items-center gap-5 px-6 text-xs text-ink-soft">
        <a href="{{ route('how') }}" class="font-medium hover:text-ink">How it works</a>
        <span class="h-4 w-px bg-line"></span>
        <a href="{{ route('pricing') }}" class="hover:text-ink">Pricing</a>
        <a href="{{ route('how') }}#trust" class="hover:text-ink">Trust &amp; safety</a>
        <a href="{{ route('how') }}#faq" class="hover:text-ink">Help</a>
        <span class="ml-auto text-faint">Project payments go directly to creatives — no commission</span>
        <x-logo class="scale-75"/>
    </div>
</footer>
<x-flash/>
</body>
</html>
