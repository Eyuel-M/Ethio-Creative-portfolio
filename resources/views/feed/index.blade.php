@php
    $user = auth()->user();
    $hour = now()->timezone($user->timezone ?? 'Africa/Addis_Ababa')->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $filtered = ($f['q'] ?? null) || ($f['category'] ?? null) || $sort !== 'recommended';
@endphp
<x-layouts.app title="Discover">
<section class="card card-pad">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-muted">{{ $greet }}, {{ explode(' ', $user->name)[0] }}</p>
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-[34px]">Discover creative work</h1>
        </div>
        <div class="flex gap-2">
            @if($user->workspace() === 'client')
                <a href="{{ route('client.jobs.create') }}" class="btn btn-light"><x-icon name="plus" class="size-4"/> Post a job</a>
            @elseif($user->freelancerProfile)
                <a href="{{ route('portfolio.create') }}" class="btn btn-light"><x-icon name="plus" class="size-4"/> Share your work</a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-dark"><x-icon name="grid" class="size-4"/> Dashboard</a>
        </div>
    </div>

    <form method="GET" action="{{ route('home') }}" class="mt-6 flex flex-col gap-3 md:flex-row" x-data>
        @if($f['category'] ?? null)<input type="hidden" name="category" value="{{ $f['category'] }}">@endif
        <label class="flex h-12 flex-1 items-center gap-3 rounded-xl border border-line bg-zinc-50/70 px-4">
            <x-icon name="search" class="size-5 text-muted"/><span class="sr-only">Search projects</span>
            <input name="q" value="{{ $f['q'] ?? '' }}" class="w-full bg-transparent text-[15px] outline-none placeholder:text-faint" placeholder="Search projects, tags or creatives">
        </label>
        <label class="flex h-12 items-center gap-2 rounded-xl border border-line px-4 text-sm"><span class="text-faint">Sort</span>
            <select name="sort" @change="$el.form.requestSubmit()" class="cursor-pointer bg-transparent font-semibold outline-none">
                @foreach(['recommended' => 'Recommended', 'newest' => 'Newest', 'most_liked' => 'Most liked', 'most_viewed' => 'Most viewed'] as $v => $l)<option value="{{ $v }}" @selected($sort === $v)>{{ $l }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-dark h-12 px-6">Search</button>
    </form>

    {{-- Category chips --}}
    <nav class="-mx-1 mt-5 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none]" aria-label="Categories">
        <a href="{{ route('home', array_filter(['q' => $f['q'] ?? null, 'sort' => $sort !== 'recommended' ? $sort : null])) }}" class="btn btn-sm btn-pill shrink-0 {{ empty($f['category']) ? 'btn-dark' : 'btn-light' }}">All</a>
        @foreach($categories as $c)
            <a href="{{ route('home', array_filter(['category' => $c->slug, 'q' => $f['q'] ?? null, 'sort' => $sort !== 'recommended' ? $sort : null])) }}" class="btn btn-sm btn-pill shrink-0 {{ ($f['category'] ?? '') === $c->slug ? 'btn-dark' : 'btn-light' }}"><x-icon :name="$c->icon ?? 'pen'" class="size-4"/>{{ $c->name }}</a>
        @endforeach
    </nav>
</section>

{{-- Bento: featured work --}}
@if($bento->isNotEmpty())
<section class="card card-pad">
    <div class="flex items-center justify-between"><h2 class="panel-title">Featured this season</h2><span class="text-xs text-muted">Most viewed &amp; liked recently</span></div>
    <div class="mt-5 grid auto-rows-[220px] gap-3 sm:gap-4 md:grid-cols-4">
        @foreach($bento as $i => $p)
            <x-feed-card :project="$p" :rating="$ratings[$p->profile->user_id] ?? null" :liked="in_array($p->id, $likedIds)" :size="$i === 0 ? 'large' : 'tile'"
                class="{{ $i === 0 ? 'md:col-span-2 md:row-span-2' : '' }} [&>div]:!min-h-0 [&>div]:h-full"/>
        @endforeach
    </div>
</section>
@endif

{{-- Main grid --}}
<section class="card card-pad">
    <div class="flex items-center justify-between">
        <h2 class="panel-title">{{ $filtered ? 'Results' : 'More to explore' }} <span class="text-base font-normal text-faint">({{ number_format($projects->total()) }})</span></h2>
        @if($filtered)<a href="{{ route('home') }}" class="text-sm font-medium text-leaf-dark hover:underline">Clear filters</a>@endif
    </div>
    @if($projects->isEmpty())
        <x-empty class="mt-5" icon="image" title="No projects found" text="Try another category or search term."/>
    @else
        <div x-data="{ next: @js($projects->nextPageUrl()), loading: false,
                      async more() {
                          this.loading = true;
                          const html = await (await fetch(this.next)).text();
                          const doc = new DOMParser().parseFromString(html, 'text/html');
                          doc.querySelectorAll('#feed-grid > *').forEach(n => this.$refs.grid.appendChild(document.importNode(n, true)));
                          this.next = doc.querySelector('[data-next]')?.dataset.next || null;
                          this.loading = false;
                      } }">
            <div id="feed-grid" x-ref="grid" class="mt-5 grid gap-x-4 gap-y-7 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach($projects as $p)
                    <x-feed-card :project="$p" :rating="$ratings[$p->profile->user_id] ?? null" :liked="in_array($p->id, $likedIds)"/>
                @endforeach
            </div>
            <span data-next="{{ $projects->nextPageUrl() }}" hidden></span>
            <div class="mt-8 text-center" x-show="next">
                <button type="button" class="btn btn-light btn-pill px-8" @click="more()" :disabled="loading"><span x-text="loading ? 'Loading…' : 'Load more'"></span></button>
            </div>
            <noscript>{{ $projects->links() }}</noscript>
        </div>
    @endif
</section>
</x-layouts.app>
