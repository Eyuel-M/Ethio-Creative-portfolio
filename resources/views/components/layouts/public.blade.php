@props(['title' => null, 'hero' => false])
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => $title])</head>
<body class="min-h-screen bg-[#fbfbfd]" x-data="{ nav: false }">
<header class="{{ $hero ? 'absolute inset-x-0 top-0 z-30' : 'sticky top-0 z-30 border-b border-line bg-white/85 backdrop-blur' }}">
    <div class="mx-auto flex h-20 max-w-[1320px] items-center justify-between gap-4 px-4 sm:px-6">
        <x-logo/>
        <nav class="hidden items-center gap-1 rounded-full border border-white/70 bg-white/60 p-1 text-sm shadow-[0_1px_2px_rgba(0,0,0,.03)] backdrop-blur md:flex">
            @foreach([['Home', 'home'], ['Talent', 'freelancers.index'], ['Jobs', 'jobs.index'], ['How it works', 'how'], ['Pricing', 'pricing']] as [$label, $route])
                <a href="{{ route($route) }}" class="rounded-full px-4 py-2 {{ request()->routeIs($route) ? 'bg-white font-medium text-ink shadow-sm' : 'text-ink-soft hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="hidden items-center gap-2 md:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-dark btn-pill h-10">Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'freelancer']) }}" class="btn btn-dark btn-pill h-10 text-[13px]">Join as Freelancer</a>
                <a href="{{ route('login') }}" class="btn btn-pill h-10 bg-white/80 text-[13px] text-ink-soft hover:text-ink">Sign in</a>
            @endauth
        </div>
        <button class="rounded-lg p-2 md:hidden" @click="nav = !nav" aria-label="Menu"><x-icon name="menu"/></button>
    </div>
    <div x-show="nav" x-cloak x-transition class="mx-4 mb-3 rounded-2xl border border-line bg-white p-2 shadow-lg md:hidden">
        @foreach([['Home', 'home'], ['Talent', 'freelancers.index'], ['Jobs', 'jobs.index'], ['How it works', 'how'], ['Pricing', 'pricing']] as [$label, $route])
            <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2.5 text-sm hover:bg-zinc-50">{{ $label }}</a>
        @endforeach
        <div class="mt-2 grid grid-cols-2 gap-2">
            @auth <a href="{{ route('dashboard') }}" class="btn btn-dark col-span-2">Dashboard</a>
            @else <a href="{{ route('login') }}" class="btn btn-light">Sign in</a><a href="{{ route('register') }}" class="btn btn-dark">Join</a> @endauth
        </div>
    </div>
</header>

<main>{{ $slot }}</main>

<footer class="mt-10 border-t border-line bg-white">
    <div class="mx-auto grid max-w-[1320px] gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <x-logo/>
            <p class="mt-4 max-w-xs text-sm leading-relaxed text-muted">A portfolio-first home for Ethiopian creative talent. Clients pay directly — we never hold or take a cut of project payments.</p>
        </div>
        <div><p class="text-sm font-semibold">Discover</p><ul class="mt-3 space-y-2 text-sm text-muted"><li><a class="hover:text-ink" href="{{ route('freelancers.index') }}">Browse talent</a></li><li><a class="hover:text-ink" href="{{ route('jobs.index') }}">Find jobs</a></li><li><a class="hover:text-ink" href="{{ route('pricing') }}">Pricing</a></li></ul></div>
        <div><p class="text-sm font-semibold">Platform</p><ul class="mt-3 space-y-2 text-sm text-muted"><li><a class="hover:text-ink" href="{{ route('how') }}">How it works</a></li><li><a class="hover:text-ink" href="{{ route('how') }}#trust">Trust &amp; safety</a></li><li><a class="hover:text-ink" href="{{ route('how') }}#faq">FAQ</a></li></ul></div>
        <div><p class="text-sm font-semibold">Get started</p><ul class="mt-3 space-y-2 text-sm text-muted"><li><a class="hover:text-ink" href="{{ route('register', ['role' => 'freelancer']) }}">Join as freelancer</a></li><li><a class="hover:text-ink" href="{{ route('register', ['role' => 'client']) }}">Hire talent</a></li><li><a class="hover:text-ink" href="{{ route('login') }}">Sign in</a></li></ul></div>
    </div>
    <div class="border-t border-line py-5 text-center text-xs text-faint">© {{ date('Y') }} {{ config('marketplace.brand') }}. Platform fees only — project payments are made directly between client and freelancer.</div>
</footer>
<x-flash/>
</body>
</html>
