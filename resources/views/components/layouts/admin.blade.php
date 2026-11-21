@props(['title' => null])
@php
    $me = auth()->user();
    $menu = \App\Support\AdminMenu::for($me);
    $counts = \App\Support\AdminMenu::counts();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => ($title ? $title.' · ' : '').'Admin'])<meta name="robots" content="noindex"></head>
<body class="min-h-screen bg-zinc-100" x-data="{ side: false }">

{{-- ───────── Sidebar ───────── --}}
<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-[#0c0c10] text-zinc-300 transition-transform lg:translate-x-0" :class="side && 'translate-x-0'" aria-label="Admin navigation">
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/10 px-5">
        <span class="grid size-8 place-items-center rounded-lg bg-white text-ink"><x-icon name="shield" class="size-4"/></span>
        <div class="leading-tight"><p class="font-display text-[15px] font-bold text-white">{{ config('marketplace.brand') }}</p><p class="text-[11px] uppercase tracking-[0.16em] text-zinc-500">Admin console</p></div>
    </div>
    <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4 text-[13.5px]">
        @foreach($menu as $group => $items)
            <div>
                @if($group)<p class="mb-1.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ $group }}</p>@endif
                <ul class="space-y-0.5">
                    @foreach($items as [$label, $route, $icon, $section, $countKey])
                        @php $active = request()->routeIs($route) || request()->routeIs($route.'.*'); $n = $countKey ? ($counts[$countKey] ?? 0) : 0; @endphp
                        <li>
                            <a href="{{ route($route) }}" @if($active) aria-current="page" @endif
                               class="flex items-center gap-3 rounded-lg px-3 py-2 transition {{ $active ? 'bg-white/10 font-medium text-white' : 'hover:bg-white/5 hover:text-white' }}">
                                <x-icon :name="$icon" class="size-[17px] shrink-0 {{ $active ? 'text-white' : 'text-zinc-500' }}"/>
                                <span class="flex-1 truncate">{{ $label }}</span>
                                @if($n)<span class="min-w-5 rounded-full px-1.5 py-px text-center text-[11px] font-semibold tabular-nums {{ $countKey === 'health' ? 'bg-rose-500 text-white' : 'bg-white/15 text-white' }}">{{ $n > 99 ? '99+' : $n }}</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
    <div class="border-t border-white/10 p-3">
        <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-[13.5px] hover:bg-white/5 hover:text-white"><x-icon name="arrow-up-right" class="size-[17px] text-zinc-500"/> Exit to site</a>
    </div>
</aside>
<div x-show="side" x-cloak @click="side = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

<div class="lg:pl-64">
    {{-- ───────── Top bar ───────── --}}
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/95 px-4 backdrop-blur sm:px-6">
        <button type="button" class="-ml-1 grid size-10 place-items-center rounded-lg hover:bg-zinc-100 lg:hidden" @click="side = true" aria-label="Open menu"><x-icon name="menu"/></button>

        @if($me->canAdmin('search'))
        <form action="{{ route('admin.search') }}" class="relative max-w-xl flex-1" x-data="{ q: @js(request('q', '')), open: false, items: [], async go() {
                if (this.q.trim().length < 2) { this.items = []; return; }
                const r = await fetch(@js(route('admin.search.suggest')) + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                if (r.ok) { this.items = await r.json(); this.open = true; }
            } }" @click.outside="open = false" @keydown.escape="open = false">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/>
            <input name="q" x-model="q" @input.debounce.250ms="go()" @focus="items.length && (open = true)" autocomplete="off" class="h-10 w-full rounded-xl border border-line bg-zinc-50 pl-9 pr-16 text-sm placeholder:text-faint focus:border-ink focus:bg-white focus:outline-none" placeholder="Search users, jobs, projects, orders, IDs…">
            <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded border border-line bg-white px-1.5 text-[10px] text-muted sm:block">Enter</kbd>
            <div x-show="open && items.length" x-cloak class="absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                <template x-for="it in items" :key="it.url">
                    <a :href="it.url" class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-zinc-50">
                        <span class="w-20 shrink-0 text-[11px] font-semibold uppercase tracking-wider text-faint" x-text="it.type"></span>
                        <span class="min-w-0"><span class="block truncate text-sm font-medium" x-text="it.title"></span><span class="block truncate text-xs text-muted" x-text="it.sub"></span></span>
                    </a>
                </template>
            </div>
        </form>
        @else
            <div class="flex-1"></div>
        @endif

        <div class="ml-auto flex items-center gap-2">
            @unless(app()->isProduction())<span class="hidden rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-amber-800 sm:inline">{{ app()->environment() }}</span>@endunless
            <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                <button @click="open = !open" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 hover:bg-zinc-100" :aria-expanded="open">
                    <x-avatar :user="$me" size="size-8" class="text-[11px]"/>
                    <span class="hidden text-left leading-tight sm:block"><span class="block text-[13px] font-semibold">{{ $me->name }}</span><span class="block text-[11px] text-muted">{{ $me->adminRoleLabel() }}</span></span>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right class="absolute right-0 top-full z-50 mt-2 w-56 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="arrow-up-right" class="size-4 text-muted"/> View site</a>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="grid" class="size-4 text-muted"/> My dashboard</a>
                    <div class="my-1 h-px bg-line"></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4"/> Sign out</button></form>
                </div>
            </div>
        </div>
    </header>

    <main class="space-y-5 p-4 sm:p-6">
        @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        {{ $slot }}
    </main>
</div>
<x-flash/>
</body>
</html>
