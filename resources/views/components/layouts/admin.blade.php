@props(['title' => null])
@php
    $me = auth()->user();
    $menu = \App\Support\AdminMenu::for($me);
    $counts = \App\Support\AdminMenu::counts();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => ($title ? $title.' · ' : '').'Admin'])<meta name="robots" content="noindex"></head>
<body class="min-h-screen bg-canvas" x-data="{ side: false }" @keydown.escape="side = false" @open-console.window="side = true">
@include('partials.site-banners')

{{-- The same top bar as every other page in admin mode, so the console is part of the site, not a separate app. --}}
<x-app-nav :counts="$navCounts ?? []"/>

<div class="lg:grid lg:grid-cols-[256px_minmax(0,1fr)]">
    {{-- ───────── Console menu ─────────
         Desktop: a sticky panel under the top bar that scrolls on its own.
         Phones/tablets: a drawer opened from the toolbar. --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-line bg-white shadow-xl transition-transform lg:sticky lg:top-16 lg:z-10 lg:h-[calc(100dvh-4rem)] lg:w-auto lg:translate-x-0 lg:shadow-none"
           :class="side && 'translate-x-0'" aria-label="Console navigation">
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-line px-4 lg:hidden">
            <p class="font-display text-[15px] font-bold">Console</p>
            <button type="button" @click="side = false" class="grid size-9 place-items-center rounded-lg hover:bg-zinc-100" aria-label="Close menu"><x-icon name="x" class="size-5"/></button>
        </div>
        <nav class="flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 py-4 text-[13.5px]">
            {{-- Small screens: the site links from the top bar live here too, so there's one menu. --}}
            <div class="lg:hidden">
                <p class="mb-1.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.14em] text-faint">Site</p>
                <ul class="space-y-0.5">
                    @foreach([['Explore', 'home', 'sparkles'], ['Talent', 'freelancers.index', 'users'], ['Jobs', 'jobs.index', 'briefcase']] as [$label, $route, $icon])
                        <li><a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-ink-soft transition hover:bg-zinc-100 hover:text-ink"><x-icon :name="$icon" class="size-[17px] shrink-0 text-muted"/> {{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            @foreach($menu as $group => $items)
                <div>
                    @if($group)<p class="mb-1.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.14em] text-faint">{{ $group }}</p>@endif
                    <ul class="space-y-0.5">
                        @foreach($items as [$label, $route, $icon, $section, $countKey])
                            @php $active = request()->routeIs($route) || request()->routeIs($route.'.*'); $n = $countKey ? ($counts[$countKey] ?? 0) : 0; @endphp
                            <li>
                                <a href="{{ route($route) }}" @if($active) aria-current="page" @endif
                                   class="flex items-center gap-3 rounded-xl px-3 py-2 transition {{ $active ? 'bg-ink font-medium text-white' : 'text-ink-soft hover:bg-zinc-100 hover:text-ink' }}">
                                    <x-icon :name="$icon" class="size-[17px] shrink-0 {{ $active ? 'text-white' : 'text-muted' }}"/>
                                    <span class="flex-1 truncate">{{ $label }}</span>
                                    @if($n)<span class="min-w-5 rounded-full px-1.5 py-px text-center text-[11px] font-semibold tabular-nums {{ $countKey === 'health' ? 'bg-rose-500 text-white' : ($active ? 'bg-white/20 text-white' : 'bg-zinc-100 text-ink') }}">{{ $n > 99 ? '99+' : $n }}</span>@endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
        <div class="border-t border-line px-4 py-3 text-xs text-muted">
            Signed in as <span class="font-medium text-ink">{{ $me->adminRoleLabel() }}</span>
            @unless(app()->isProduction())<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-800">{{ app()->environment() }}</span>@endunless
        </div>
    </aside>
    <div x-show="side" x-cloak @click="side = false" class="fixed inset-0 z-40 bg-black/40 lg:hidden"></div>

    <div class="min-w-0">
        {{-- Toolbar: search everything (on small screens the top bar's menu button opens the console menu) --}}
        <div class="flex items-center gap-3 px-3 pt-4 sm:px-6 sm:pt-6">
            @if($me->canAdmin('search'))
            <form action="{{ route('admin.search') }}" class="relative max-w-xl flex-1" x-data="{ q: @js(request('q', '')), open: false, items: [], async go() {
                    if (this.q.trim().length < 2) { this.items = []; return; }
                    const r = await fetch(@js(route('admin.search.suggest')) + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                    if (r.ok) { this.items = await r.json(); this.open = true; }
                } }" @click.outside="open = false" @keydown.escape="open = false">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/>
                <input name="q" x-model="q" @input.debounce.250ms="go()" @focus="items.length && (open = true)" autocomplete="off" class="h-10 w-full rounded-xl border border-line bg-white pl-9 pr-16 text-sm placeholder:text-faint focus:border-ink focus:outline-none" placeholder="Search users, jobs, projects, orders, IDs…">
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
            @endif
        </div>

        <main class="space-y-5 px-3 py-4 sm:px-6 sm:py-6">
            @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            {{ $slot }}
        </main>
    </div>
</div>
<x-flash/>
</body>
</html>
