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

        <x-top-actions class="ml-auto" :counts="$navCounts ?? []" :mobileLinks="true"/>
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
        <x-logo class="scale-75"/>
    </div>
</footer>
<x-flash/>
</body>
</html>
