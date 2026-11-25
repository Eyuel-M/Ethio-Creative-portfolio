@php
    use App\Models\Post;
    $guest = ! auth()->check();
    $keep = fn (array $over = []) => array_filter(array_merge(['type' => $f['type'] ?? null, 'for' => $f['for'] ?? null, 'topic' => $f['topic'] ?? null, 'q' => $f['q'] ?? null], $over), fn ($v) => $v !== null && $v !== '');
    $total = $typeCounts->sum();
@endphp
<x-dynamic-component :component="$guest ? 'layouts.public' : 'layouts.discover'" title="Insights">
@push('meta')
<meta name="description" content="Studies, guides, product updates and creator spotlights from {{ config('marketplace.brand') }} — the portfolio-first marketplace for Ethiopian creatives.">
<meta property="og:title" content="Insights — {{ config('marketplace.brand') }}">
<meta property="og:type" content="website">
<link rel="alternate" type="application/rss+xml" title="{{ config('marketplace.brand') }} Insights" href="{{ route('insights.feed') }}">
@endpush

{{-- ───────── Hero ───────── --}}
<section class="px-3 pt-3 sm:px-4 sm:pt-4">
    <div class="hero-gradient relative overflow-hidden rounded-[28px] px-5 pb-8 pt-12 sm:px-10 sm:pb-10 sm:pt-16">
        <div class="pointer-events-none absolute -right-24 -top-24 size-[420px] rounded-full border border-white/70"></div>
        <div class="pointer-events-none absolute -right-8 -top-8 size-[260px] rounded-full border border-white/60"></div>
        <div class="relative mx-auto max-w-[1240px]">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold shadow-sm backdrop-blur"><span class="grid size-5 place-items-center rounded-full bg-ink text-white"><x-logo-mark class="size-3"/></span> Insights</p>
                    <h1 class="mt-4 font-display text-[40px] font-bold leading-[1.04] tracking-[-0.035em] sm:text-6xl">Studies, guides & news<br class="hidden sm:block"> for Ethiopia's creatives</h1>
                    <p class="mt-4 max-w-xl text-base text-ink-soft sm:text-lg">Research on how creative work is priced and hired, practical guides for freelancers and clients, and everything new on {{ config('marketplace.brand') }}.</p>
                </div>
                <form action="{{ route('insights.index') }}" class="flex w-full max-w-sm items-center rounded-full bg-white/90 p-1.5 pl-4 shadow-[0_20px_40px_-24px_rgba(60,60,120,.45)] backdrop-blur focus-within:ring-2 focus-within:ring-ink/10">
                    @foreach($keep(['q' => null]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <x-icon name="search" class="size-5 shrink-0 text-muted"/>
                    <label for="insights-q" class="sr-only">Search Insights</label>
                    <input id="insights-q" name="q" value="{{ $f['q'] ?? '' }}" class="h-10 min-w-0 flex-1 bg-transparent px-3 text-[15px] outline-none placeholder:text-muted" placeholder="Search articles…">
                    <button class="btn btn-dark btn-pill h-10 px-5">Search</button>
                </form>
            </div>

            {{-- Filters --}}
            <div class="mt-8 flex flex-wrap items-center gap-2">
                <a href="{{ route('insights.index', $keep(['type' => null])) }}" class="inline-flex h-9 items-center gap-2 rounded-full px-4 text-sm font-semibold transition {{ empty($f['type']) ? 'bg-ink text-white' : 'bg-white/80 text-ink hover:bg-white' }}">All <span class="text-xs tabular-nums opacity-60">{{ $total }}</span></a>
                @foreach(Post::TYPES as $v => [$label, $plural, $icon])
                    @continue(! ($typeCounts[$v] ?? 0))
                    <a href="{{ route('insights.index', $keep(['type' => ($f['type'] ?? null) === $v ? null : $v])) }}" class="inline-flex h-9 items-center gap-2 rounded-full px-4 text-sm font-semibold transition {{ ($f['type'] ?? null) === $v ? 'bg-ink text-white' : 'bg-white/80 text-ink hover:bg-white' }}">
                        <x-icon :name="$icon" class="size-4"/> {{ $plural }} <span class="text-xs tabular-nums opacity-60">{{ $typeCounts[$v] }}</span>
                    </a>
                @endforeach
                <span class="mx-1 hidden h-6 w-px bg-ink/10 sm:block"></span>
                <div class="flex rounded-full bg-white/80 p-1 text-sm" role="group" aria-label="Written for">
                    @foreach(['' => 'Everyone', 'freelancers' => 'Freelancers', 'clients' => 'Clients'] as $v => $label)
                        <a href="{{ route('insights.index', $keep(['for' => $v ?: null])) }}" class="rounded-full px-3.5 py-1.5 font-medium transition {{ ($f['for'] ?? '') === $v ? 'bg-ink text-white' : 'text-ink-soft hover:text-ink' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            @if($topics->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center gap-1.5 text-sm">
                    <span class="mr-1 text-muted">Topics</span>
                    @foreach($topics as $t)
                        <a href="{{ route('insights.index', $keep(['topic' => ($f['topic'] ?? null) === $t ? null : $t])) }}" class="rounded-full border px-3 py-1 transition {{ ($f['topic'] ?? null) === $t ? 'border-ink bg-ink text-white' : 'border-white bg-white/50 text-ink-soft hover:bg-white' }}">{{ $t }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>

<div class="mx-auto max-w-[1320px] px-4 pb-16 sm:px-6">
    {{-- ───────── Lead story ───────── --}}
    @if($lead)
        <article class="group relative mt-10 grid grid-cols-1 items-center gap-6 lg:grid-cols-[1.35fr_1fr] lg:gap-12">
            <div class="relative aspect-[16/10] overflow-hidden rounded-3xl bg-gradient-to-br {{ $lead->accent('grad') }}">
                @if($lead->coverUrl())
                    <img src="{{ $lead->coverUrl() }}" alt="{{ $lead->cover_alt ?? '' }}" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-[1.02]">
                @else
                    <div class="absolute inset-0 grid-lines"></div>
                    <span class="absolute bottom-6 left-6 grid size-16 place-items-center rounded-3xl bg-white/80 shadow backdrop-blur {{ $lead->accent('text') }}"><x-icon :name="$lead->typeIcon()" class="size-8"/></span>
                @endif
            </div>
            <div>
                <p class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em]">
                    <span class="{{ $lead->accent('text') }}">{{ $lead->featured_at ? 'Featured' : 'Latest' }} · {{ $lead->typeLabel() }}</span>
                </p>
                <h2 class="mt-3 font-display text-3xl font-bold leading-[1.08] tracking-tight sm:text-[44px]">
                    <a href="{{ route('insights.show', $lead) }}" class="after:absolute after:inset-0 decoration-[3px] underline-offset-[6px] group-hover:underline">{{ $lead->title }}</a>
                </h2>
                @if($lead->excerpt)<p class="mt-4 text-lg leading-relaxed text-ink-soft">{{ $lead->excerpt }}</p>@endif
                <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                    <span class="text-muted">{{ $lead->published_at->format('M j, Y') }}@if($lead->shows('reading_time')) · {{ $lead->reading_minutes }} min read @endif</span>
                </p>
                <span class="relative mt-6 inline-flex items-center gap-2 text-sm font-semibold">Read the {{ strtolower($lead->typeLabel()) }} <span class="grid size-8 place-items-center rounded-full bg-ink text-white transition group-hover:translate-x-1"><x-icon name="arrow-right" class="size-4"/></span></span>
            </div>
        </article>
    @endif

    {{-- ───────── Studies band ───────── --}}
    @if($studies->count() >= 2)
        <section class="relative mt-16 overflow-hidden rounded-[28px] bg-ink px-6 py-10 text-white sm:px-10 sm:py-12">
            <div class="pointer-events-none absolute inset-0 opacity-[0.07] [background-image:linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] [background-size:48px_48px]"></div>
            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/50">Research</p>
                    <h2 class="mt-2 font-display text-3xl font-bold tracking-tight sm:text-4xl">Studies & reports</h2>
                    <p class="mt-2 max-w-lg text-white/60">Data on rates, demand and how creative work gets hired in Ethiopia.</p>
                </div>
                <a href="{{ route('insights.index', ['type' => 'study']) }}" class="btn btn-pill h-11 bg-white px-5 text-ink hover:bg-zinc-100">All studies <x-icon name="arrow-right" class="size-4"/></a>
            </div>
            <div class="relative mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach($studies as $i => $s)
                    <a href="{{ route('insights.show', $s) }}" class="group flex flex-col rounded-2xl bg-white/[0.06] p-5 ring-1 ring-white/10 transition hover:bg-white/[0.1]">
                        <span class="font-display text-5xl font-bold tabular-nums text-white/15">0{{ $i + 1 }}</span>
                        <span class="mt-6 font-display text-xl font-bold leading-snug group-hover:underline decoration-2 underline-offset-4">{{ $s->title }}</span>
                        @if($s->excerpt)<span class="mt-2 line-clamp-2 text-sm text-white/60">{{ $s->excerpt }}</span>@endif
                        <span class="mt-auto pt-6 text-xs text-white/45">{{ $s->published_at->format('M Y') }} · {{ $s->reading_minutes }} min read</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ───────── Latest ───────── --}}
    <section class="mt-16">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <h2 class="font-display text-2xl font-bold tracking-tight sm:text-[28px]">
                @if($filtered)
                    {{ $posts->total() }} {{ \Illuminate\Support\Str::plural('post', $posts->total()) }}@if($f['q'] ?? null) for “{{ $f['q'] }}”@endif
                @else
                    Latest
                @endif
            </h2>
            <div class="flex items-center gap-4 text-sm">
                @if($filtered)<a href="{{ route('insights.index') }}" class="font-medium text-leaf-dark hover:underline">Clear filters</a>@endif
                <a href="{{ route('insights.feed') }}" class="inline-flex items-center gap-1.5 text-muted hover:text-ink" title="Follow with an RSS reader"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 11a8 8 0 0 1 8 8M5 5a14 14 0 0 1 14 14"/><circle cx="6" cy="18" r="1.4" fill="currentColor"/></svg> RSS</a>
            </div>
        </div>
        @if($posts->isEmpty() && ! $lead)
            <x-empty icon="pen" :title="$filtered ? 'Nothing matches yet' : 'Insights are on the way'" :text="$filtered ? 'Try another type or topic, or clear the search.' : 'Studies, guides and news will appear here soon.'"/>
        @else
            <div class="grid grid-cols-1 gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $p)
                    <x-post-card :post="$p"/>
                @endforeach
            </div>
            @if($posts->hasPages())<div class="mt-12">{{ $posts->links() }}</div>@endif
        @endif
    </section>

    {{-- ───────── Join ───────── --}}
    @if($guest)
        <section class="relative mt-20 overflow-hidden rounded-[28px] border border-line bg-white px-6 py-12 text-center sm:px-12">
            <div class="pointer-events-none absolute inset-0 grid-lines opacity-70"></div>
            <div class="relative mx-auto max-w-2xl">
                <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Put these insights to work</h2>
                <p class="mt-3 text-ink-soft">Build a portfolio that gets you hired, or find the right creative for your next project.</p>
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register', ['role' => 'freelancer']) }}" class="btn btn-dark btn-pill h-12 px-6">Join as a freelancer</a>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-light btn-pill h-12 px-6">Hire talent</a>
                </div>
            </div>
        </section>
    @endif
</div>
</x-dynamic-component>
