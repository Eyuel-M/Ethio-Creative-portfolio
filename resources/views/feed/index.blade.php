@php
    $user = auth()->user();
    $ws = $user?->workspace();
    $filtered = ($f['q'] ?? null) || ($f['category'] ?? null) || $sort !== 'recommended' || $filterCount;
    $sorts = ['recommended' => 'Recommended', 'newest' => 'Newest', 'most_liked' => 'Most liked', 'most_viewed' => 'Most viewed'];
    $keep = fn (array $over = []) => array_filter(array_merge(['q' => $f['q'] ?? null, 'category' => $f['category'] ?? null, 'sort' => $sort !== 'recommended' ? $sort : null,
        'available' => ($f['available'] ?? false) ? 1 : null, 'pro' => ($f['pro'] ?? false) ? 1 : null, 'min_likes' => ($f['min_likes'] ?? 0) ?: null], $over), fn ($v) => $v !== null && $v !== '');
    $promo = ! $user ? null : ($ws === 'client' ? 'client' : (($user->freelancerProfile && $user->freelancerProfile->plan !== 'professional') ? 'pro' : null));
@endphp
<x-dynamic-component :component="$user ? 'layouts.discover' : 'layouts.public'" title="Explore">

@auth
{{-- Toolbar: Filter · Search (Projects / People / Jobs) · Sort --}}
<form method="GET" action="{{ route($feedRoute) }}" x-data="{ filters: {{ $filterCount ? 'true' : 'false' }}, tab: 'projects', actions: { projects: @js(route($feedRoute)), people: @js(route('freelancers.index')), jobs: @js(route('jobs.index')) } }"
      :action="actions[tab]" @submit="if (tab !== 'projects') $el.querySelectorAll('[data-feed-only]').forEach(e => e.disabled = true)" class="border-b border-line">
    <div class="flex flex-wrap items-center gap-3 px-4 py-4 sm:px-6 lg:flex-nowrap">
        <button type="button" @click="filters = !filters" :aria-expanded="filters" class="btn btn-light btn-pill h-12 shrink-0 px-5" :class="filters && 'border-ink'">
            <x-icon name="filter" class="size-5"/> Filter
            @if($filterCount)<span class="grid size-5 place-items-center rounded-full bg-ink text-[11px] text-white">{{ $filterCount }}</span>@endif
        </button>

        <div class="order-last flex h-12 w-full items-center rounded-full bg-zinc-100 pl-5 pr-1.5 focus-within:bg-white focus-within:ring-2 focus-within:ring-ink/10 lg:order-none lg:w-auto lg:flex-1">
            <x-icon name="search" class="size-5 shrink-0 text-muted"/>
            <label for="feed-q" class="sr-only">Search</label>
            <input id="feed-q" name="q" value="{{ $f['q'] ?? '' }}" class="h-full min-w-0 flex-1 bg-transparent px-3 text-[15px] outline-none placeholder:text-muted" :placeholder="{ projects: 'Search projects, tags or creatives…', people: 'Search creatives by name or skill…', jobs: 'Search jobs…' }[tab]" placeholder="Search projects, tags or creatives…">
            <div class="hidden items-center gap-0.5 sm:flex" role="tablist" aria-label="Search in">
                @foreach(['projects' => 'Projects', 'people' => 'People', 'jobs' => 'Jobs'] as $k => $l)
                    <button type="button" role="tab" @click="tab = '{{ $k }}'" :aria-selected="tab === '{{ $k }}'" class="h-9 rounded-full px-4 text-sm font-medium transition" :class="tab === '{{ $k }}' ? 'bg-white text-ink shadow-sm ring-1 ring-line' : 'text-ink-soft hover:text-ink'">{{ $l }}</button>
                @endforeach
            </div>
        </div>

        <label class="ml-auto flex h-12 shrink-0 cursor-pointer items-center gap-2 px-2 text-[15px] font-medium lg:ml-0">
            <x-icon name="list" class="size-5"/>
            <span class="sr-only">Sort by</span>
            <select name="sort" data-feed-only onchange="this.form.requestSubmit()" class="cursor-pointer appearance-none bg-transparent font-semibold outline-none">
                @foreach($sorts as $v => $l)<option value="{{ $v }}" @selected($sort === $v)>{{ $l }}</option>@endforeach
            </select>
            <x-icon name="chevron-down" class="pointer-events-none -ml-1 size-4"/>
        </label>
        @if($f['category'] ?? null)<input type="hidden" name="category" value="{{ $f['category'] }}">@endif
    </div>

    {{-- Filter panel --}}
    <div x-show="filters" x-cloak x-transition class="border-t border-line bg-zinc-50/60 px-4 py-4 sm:px-6">
        <div class="flex flex-wrap items-center gap-x-8 gap-y-3 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="available" data-feed-only value="1" class="checkbox" @checked($f['available'] ?? false)> Available for hire</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="pro" data-feed-only value="1" class="checkbox" @checked($f['pro'] ?? false)> Pro creators</label>
            <fieldset class="flex items-center gap-3"><legend class="sr-only">Minimum likes</legend><span class="text-muted">Likes</span>
                @foreach([0 => 'Any', 5 => '5+', 10 => '10+'] as $v => $l)
                    <label class="cursor-pointer rounded-full border border-line bg-white px-3 py-1 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white"><input type="radio" name="min_likes" data-feed-only value="{{ $v }}" class="sr-only" @checked((int) ($f['min_likes'] ?? 0) === $v)>{{ $l }}</label>
                @endforeach
            </fieldset>
            <div class="ml-auto flex gap-2">
                <a href="{{ route($feedRoute) }}" class="btn btn-sm btn-ghost">Reset</a>
                <button class="btn btn-sm btn-dark">Show results</button>
            </div>
        </div>
    </div>
</form>
@endauth

{{-- Category tiles --}}
<div class="relative" x-data="{ el: null, end: false, update() { this.end = this.el.scrollLeft + this.el.clientWidth >= this.el.scrollWidth - 4 } }" x-init="el = $refs.row; update()">
    <nav x-ref="row" @scroll.debounce.50ms="update()" class="flex gap-2.5 overflow-x-auto scroll-smooth px-4 py-4 [scrollbar-width:none] sm:px-6 [&::-webkit-scrollbar]:hidden" aria-label="Categories">
        <a href="{{ route($feedRoute, $keep(['category' => null])) }}" class="flex h-12 shrink-0 items-center gap-2 rounded-lg bg-gradient-to-br from-[#3b7bff] to-brand px-5 text-[15px] font-bold text-white shadow-sm {{ empty($f['category']) ? 'ring-2 ring-ink ring-offset-2' : 'opacity-90 hover:opacity-100' }}">
            <x-icon name="star" class="size-5"/> For You
        </a>
        @auth
        <a href="{{ route($feedRoute, ['sort' => 'most_liked']) }}" class="flex h-12 shrink-0 items-center gap-2 rounded-lg bg-ink px-5 text-[15px] font-bold text-white {{ $sort === 'most_liked' && empty($f['category']) ? 'ring-2 ring-ink ring-offset-2' : '' }}">
            <x-icon name="heart" class="size-5"/> Most Loved
        </a>
        @endauth
        @foreach($categories as $c)
            @php $active = ($f['category'] ?? '') === $c->slug; $img = $categoryCovers[$c->id] ?? null; @endphp
            <a href="{{ route($feedRoute, $keep(['category' => $c->slug])) }}" class="relative flex h-12 min-w-[150px] shrink-0 items-center justify-center overflow-hidden rounded-lg bg-zinc-800 px-5 text-[15px] font-bold text-white {{ $active ? 'ring-2 ring-ink ring-offset-2' : '' }}">
                @if($img)<img src="{{ $img }}" alt="" class="absolute inset-0 size-full object-cover opacity-70" loading="lazy">@endif
                <span class="absolute inset-0 bg-gradient-to-r from-black/55 to-black/25"></span>
                <span class="relative drop-shadow">{{ $c->name }}</span>
            </a>
        @endforeach
    </nav>
    <button type="button" x-show="!end" @click="el.scrollBy({ left: el.clientWidth * .7 })" class="absolute right-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full border border-line bg-white shadow-md hover:bg-zinc-50" aria-label="More categories">
        <x-icon name="chevron-right" class="size-5"/>
    </button>
    <div x-show="!end" class="pointer-events-none absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-white to-transparent"></div>
</div>

{{-- Promo strip (dismissible), like Behance's "Upgrade to Pro" --}}
@if($promo && ! $filtered)
<section x-data="{ show: (() => { try { return localStorage.getItem('promo-{{ $promo }}') !== 'hidden' } catch (e) { return true } })() }" x-show="show" x-cloak
         class="relative bg-zinc-100">
    <button type="button" @click="show = false; try { localStorage.setItem('promo-{{ $promo }}', 'hidden') } catch (e) {}" class="absolute right-4 top-4 z-10 grid size-8 place-items-center rounded-full text-muted hover:bg-white hover:text-ink" aria-label="Dismiss"><x-icon name="x" class="size-4"/></button>
    <div class="grid gap-6 px-4 py-8 sm:px-6 xl:grid-cols-[320px_1fr] xl:pr-16">
        <div class="flex flex-col justify-center">
            <div class="relative w-fit">
                <x-avatar :user="$user" size="size-20" class="text-xl ring-4 ring-white"/>
                <span class="absolute -bottom-1 right-0 rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold text-white ring-2 ring-white">{{ $promo === 'pro' ? 'PRO' : 'HIRE' }}</span>
            </div>
            <h2 class="mt-4 font-display text-2xl font-bold">{{ $promo === 'pro' ? 'Upgrade to Professional' : 'Hire creative talent faster' }}</h2>
            <p class="mt-2 text-[15px] leading-relaxed text-ink-soft">{{ $promo === 'pro' ? 'More portfolio space, detailed analytics and optional featured placement to get seen by more clients.' : 'Publish a job with screening questions, compare applicants side by side, and pay creatives directly — no commission.' }}</p>
            <a href="{{ $promo === 'pro' ? route('billing.index') : route('client.jobs.create') }}" class="btn btn-light btn-pill mt-5 h-11 w-fit px-6 shadow-sm">{{ $promo === 'pro' ? 'See Professional plan' : 'Post a job' }}</a>
        </div>
        <div class="-mx-4 flex gap-4 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:-mx-6 sm:px-6 xl:mx-0 xl:grid xl:grid-cols-3 xl:px-0">
            @php
                $features = $promo === 'pro' ? [
                    ['layers', 'More portfolio space', config('marketplace.portfolio_limits.professional').' case studies instead of '.config('marketplace.portfolio_limits.free').', with a bigger media allowance.', 'from-[#1d2b64] to-[#2f6bff]'],
                    ['chart', 'Detailed analytics', 'See who views your work, which projects perform and where visitors come from.', 'from-[#0f2027] to-[#ff7a2f]'],
                    ['megaphone', 'Featured placement', 'Optional, clearly labelled promotion at the top of the directory and feed.', 'from-[#2b1055] to-[#8b5cf6]'],
                ] : [
                    ['list', 'Structured screening', 'Ask every applicant the same questions, from templates or your own.', 'from-[#1d2b64] to-[#2f6bff]'],
                    ['users', 'Compare side by side', 'Proposals, answers and portfolio picks in one comparison table.', 'from-[#0f2027] to-[#34c759]'],
                    ['bank', 'Pay creatives directly', 'Confirm an agreement and pay via CBE or Telebirr — we take no cut.', 'from-[#2b1055] to-[#8b5cf6]'],
                ];
            @endphp
            @foreach($features as [$icon, $title, $text, $grad])
                <div class="w-[300px] shrink-0 xl:w-auto">
                    <div class="relative h-40 overflow-hidden rounded-xl bg-gradient-to-br {{ $grad }} p-4">
                        <div class="absolute bottom-0 left-6 right-6 top-8 rounded-t-xl bg-white/95 p-3 shadow-2xl">
                            <div class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-lg bg-zinc-100"><x-icon :name="$icon" class="size-4"/></span><span class="h-2 w-24 rounded-full bg-zinc-200"></span></div>
                            <div class="mt-3 space-y-2"><span class="block h-2 w-full rounded-full bg-zinc-100"></span><span class="block h-2 w-4/5 rounded-full bg-zinc-100"></span><span class="block h-2 w-3/5 rounded-full bg-brand/30"></span></div>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-brand shadow-sm"><x-icon :name="$icon" class="size-5"/></span>
                        <div><p class="font-semibold">{{ $title }}</p><p class="mt-0.5 text-sm text-ink-soft">{{ $text }}</p></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<div class="px-4 pb-10 pt-6 sm:px-6">
    {{-- Bento: featured work --}}
    @if($bento->isNotEmpty())
        <div class="mb-3 flex items-baseline justify-between"><h2 class="font-display text-lg font-bold">Featured this season</h2><span class="text-xs text-muted">Most viewed &amp; liked recently</span></div>
        <div class="grid auto-rows-[220px] gap-4 md:grid-cols-4">
            @foreach($bento as $i => $p)
                <x-feed-card :project="$p" :rating="$ratings[$p->profile->user_id] ?? null" :liked="in_array($p->id, $likedIds)" :size="$i === 0 ? 'large' : 'tile'"
                    class="{{ $i === 0 ? 'md:col-span-2 md:row-span-2' : '' }} [&>div]:!min-h-0 [&>div]:h-full"/>
            @endforeach
        </div>
        <div class="mb-6 mt-10 h-px bg-line"></div>
    @endif

    @if($filtered)
        <div class="mb-5 flex items-center justify-between">
            <p class="text-sm text-muted"><span class="font-semibold text-ink">{{ number_format($projects->total()) }}</span> projects{{ ($f['q'] ?? null) ? ' for “'.$f['q'].'”' : '' }}</p>
            <a href="{{ route($feedRoute) }}" class="text-sm font-medium text-leaf-dark hover:underline">Clear all</a>
        </div>
    @endif

    @if($projects->isEmpty())
        <x-empty icon="image" title="No projects found" text="Try another category, fewer filters or a different search."/>
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
            @php
                $all = $projects->getCollection();
                $blurred = $user ? collect() : $all->slice(max(4, $all->count() - 8))->values();
                $shown = $user ? $all : $all->take($all->count() - $blurred->count());
                $gridClass = 'grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 min-[1700px]:grid-cols-5';
            @endphp
            <div id="feed-grid" x-ref="grid" class="{{ $gridClass }}">
                @foreach($shown as $p)
                    <x-feed-card :project="$p" :liked="in_array($p->id, $likedIds)"/>
                @endforeach
            </div>

            @guest
                {{-- Preview wall: the rest is blurred; members see everything --}}
                <div class="relative mt-8">
                    @if($blurred->isNotEmpty())
                        <div class="max-h-[560px] overflow-hidden" inert aria-hidden="true">
                            <div class="{{ $gridClass }} pointer-events-none select-none blur-[7px] saturate-75">
                                @foreach($blurred as $p)
                                    <x-feed-card :project="$p"/>
                                @endforeach
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-[#fbfbfd]/0 via-[#fbfbfd]/60 to-[#fbfbfd]"></div>
                    @endif
                    <div class="{{ $blurred->isNotEmpty() ? 'absolute inset-0' : '' }} flex items-center justify-center px-4 py-10">
                        <div class="w-full max-w-md rounded-3xl border border-line bg-white/95 p-7 text-center shadow-[0_30px_60px_-30px_rgba(12,12,16,.35)] backdrop-blur">
                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-ink text-white"><x-icon name="sparkles" class="size-6"/></span>
                            <h2 class="mt-4 font-display text-2xl font-bold">See all {{ number_format($projects->total()) }} projects</h2>
                            <p class="mt-2 text-ink-soft">Join free to browse everything, search and filter, like work and connect with creatives.</p>
                            <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                                <a href="{{ route('register') }}" class="btn btn-dark h-12 px-7">Join free</a>
                                <a href="{{ route('login') }}" class="btn btn-light h-12 px-7">Sign in</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endguest
            <span data-next="{{ $projects->nextPageUrl() }}" hidden></span>
            @auth
            <div class="mt-10 text-center" x-show="next">
                <button type="button" class="btn btn-light btn-pill px-8" @click="more()" :disabled="loading"><span x-text="loading ? 'Loading…' : 'Load more'"></span></button>
            </div>
            <noscript>{{ $projects->links() }}</noscript>
            @endauth
        </div>
    @endif
</div>
</x-dynamic-component>
