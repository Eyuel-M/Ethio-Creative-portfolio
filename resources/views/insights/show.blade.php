@php
    use App\Models\Post;
    $guest = ! auth()->check();
    $layout = $post->opt('layout');
    $coverStyle = $layout === 'minimal' ? 'none' : $post->opt('cover');
    $outline = $post->outline();
    $showToc = $post->shows('toc') && $layout !== 'minimal' && count($outline) >= 2;
    $hex = Post::ACCENTS[$post->opt('accent')]['hex'];
    $url = route('insights.show', $post);
@endphp
<x-dynamic-component :component="$guest ? 'layouts.public' : 'layouts.discover'" :title="$post->metaTitle()">
@push('meta')
<meta name="description" content="{{ $post->metaDescription() }}">
<link rel="canonical" href="{{ $url }}">
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $post->metaTitle() }}">
<meta property="og:description" content="{{ $post->metaDescription() }}">
<meta property="og:url" content="{{ $url }}">
@if($post->coverUrl())<meta property="og:image" content="{{ $post->coverUrl() }}"><meta name="twitter:card" content="summary_large_image">@endif
@if($post->published_at)<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">@endif
<link rel="alternate" type="application/rss+xml" title="{{ config('marketplace.brand') }} Insights" href="{{ route('insights.feed') }}">
@if(! $post->isPublic())<meta name="robots" content="noindex">@endif
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => $post->type === 'study' ? 'Report' : 'Article',
    'headline' => $post->title, 'description' => $post->metaDescription(), 'image' => $post->coverUrl(),
    'datePublished' => $post->published_at?->toIso8601String(), 'dateModified' => $post->updated_at?->toIso8601String(),
    'author' => ['@type' => 'Organization', 'name' => config('marketplace.brand')],
    'publisher' => ['@type' => 'Organization', 'name' => config('marketplace.brand')],
    'mainEntityOfPage' => $url, 'keywords' => implode(', ', $post->topics ?? []) ?: null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

{{-- Reading progress --}}
<div x-data="{ p: 0 }" x-init="const f = () => { const h = document.documentElement; p = Math.min(100, Math.max(0, h.scrollTop / ((h.scrollHeight - h.clientHeight) || 1) * 100)) }; f(); window.addEventListener('scroll', f, { passive: true })"
     class="pointer-events-none fixed inset-x-0 top-0 z-[70] h-[3px]" aria-hidden="true">
    <div class="h-full origin-left transition-[width] duration-75" :style="`width:${p}%; background:{{ $hex }}`"></div>
</div>

@if($staff)
    <div class="sticky top-16 z-30 border-b px-4 py-2 text-center text-sm {{ $post->isPublic() ? 'border-line bg-white/95' : 'border-amber-200 bg-amber-50 text-amber-900' }} backdrop-blur">
        @if($post->isScheduled())Scheduled — goes live {{ $post->published_at->format('M j, Y \a\t g:ia') }}. Only admins can see this preview.
        @elseif(! $post->isPublic())Preview — this post is {{ $post->status === 'archived' ? 'archived' : 'a draft' }}. Only admins can see it.
        @else You're viewing a live post · {{ number_format($post->views) }} reads.@endif
        <a href="{{ route('admin.posts.edit', $post) }}" class="ml-2 font-semibold underline underline-offset-2">Edit post</a>
    </div>
@endif

<article style="--accent: {{ $hex }}">
    {{-- ───────── Header ───────── --}}
    @if($layout === 'feature')
        <header class="px-3 pt-3 sm:px-4 sm:pt-4">
            <div class="relative isolate flex min-h-[min(78svh,720px)] items-end overflow-hidden rounded-[28px] bg-gradient-to-br {{ $post->accent('grad') }}">
                @if($post->coverUrl() && $coverStyle !== 'none')
                    <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}" class="absolute inset-0 -z-10 size-full object-cover">
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/50 to-black/20"></div>
                @else
                    <div class="absolute inset-0 -z-10 grid-lines"></div>
                @endif
                @php $onImage = $post->coverUrl() && $coverStyle !== 'none'; @endphp
                <div class="mx-auto w-full max-w-[1120px] px-6 pb-10 pt-32 sm:px-10 sm:pb-14 {{ $onImage ? 'text-white' : '' }}">
                    <a href="{{ route('insights.index', ['type' => $post->type]) }}" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $onImage ? 'bg-white/15 text-white backdrop-blur' : 'bg-white/80 '.$post->accent('text') }}"><x-icon :name="$post->typeIcon()" class="size-3.5"/> {{ $post->typeLabel() }}</a>
                    <h1 class="mt-4 max-w-4xl font-display text-4xl font-bold leading-[1.04] tracking-[-0.03em] sm:text-6xl lg:text-[68px]">{{ $post->title }}</h1>
                    @if($post->excerpt)<p class="mt-5 max-w-2xl text-lg leading-relaxed sm:text-xl {{ $onImage ? 'text-white/80' : 'text-ink-soft' }}">{{ $post->excerpt }}</p>@endif
                    @include('insights._meta', ['light' => $onImage])
                </div>
            </div>
            @if($post->cover_caption && $onImage)<p class="mx-auto max-w-[1120px] px-6 pt-3 text-xs text-muted sm:px-10">{{ $post->cover_caption }}</p>@endif
        </header>
    @else
        <header class="relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 -z-10 h-[420px] bg-gradient-to-b {{ $post->accent('grad') }}"></div>
            <div class="mx-auto max-w-[820px] px-5 pb-8 pt-12 sm:pt-16 {{ $layout === 'minimal' ? 'text-center' : '' }}">
                <nav class="flex items-center gap-2 text-sm {{ $layout === 'minimal' ? 'justify-center' : '' }}" aria-label="Breadcrumb">
                    <a href="{{ route('insights.index') }}" class="font-medium text-muted hover:text-ink">Insights</a>
                    <x-icon name="chevron-right" class="size-3.5 text-faint"/>
                    <a href="{{ route('insights.index', ['type' => $post->type]) }}" class="inline-flex items-center gap-1.5 font-semibold {{ $post->accent('text') }}"><x-icon :name="$post->typeIcon()" class="size-4"/> {{ $post->typeLabel() }}</a>
                </nav>
                <h1 class="mt-5 font-display text-[38px] font-bold leading-[1.06] tracking-[-0.03em] sm:text-[54px]">{{ $post->title }}</h1>
                @if($post->excerpt)<p class="mt-5 text-lg leading-relaxed text-ink-soft sm:text-xl">{{ $post->excerpt }}</p>@endif
                @include('insights._meta', ['light' => false])
            </div>
            @if($post->coverUrl() && $coverStyle !== 'none')
                <figure class="{{ $coverStyle === 'hero' ? 'mx-auto max-w-[1320px] px-3 sm:px-4' : 'mx-auto max-w-[1120px] px-5' }}">
                    <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}" class="block aspect-[16/8] w-full rounded-[24px] object-cover">
                    @if($post->cover_caption)<figcaption class="pt-3 text-center text-xs text-muted">{{ $post->cover_caption }}</figcaption>@endif
                </figure>
            @endif
        </header>
    @endif

    {{-- ───────── Body ───────── --}}
    <div class="relative pb-6 pt-8 sm:pt-12" @if($showToc) x-data="{
            ids: @js(array_column($outline, 'id')), active: null, pct: 0, open: false,
            get index() { return this.ids.indexOf(this.active) },
            get left() { return Math.max(1, Math.ceil({{ $post->reading_minutes }} * (1 - this.pct / 100))) },
            go(id) { this.open = false; document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }); history.replaceState(null, '', '#' + id) },
        }" x-init="
            const body = $el;
            const heads = ids.map((id) => document.getElementById(id)).filter(Boolean);
            // Current section = the last heading that has passed the top third of the screen.
            const f = () => {
                const r = body.getBoundingClientRect();
                pct = Math.round(Math.min(100, Math.max(0, (innerHeight * .35 - r.top) / (r.height - innerHeight * .4) * 100)));
                let cur = null;
                for (const h of heads) { if (h.getBoundingClientRect().top < innerHeight * .33) cur = h.id; }
                active = cur;
            };
            f(); addEventListener('scroll', f, { passive: true })" @endif>
        @if($showToc)
            @php
                $h2 = 0;
                $items = collect($outline)->map(function ($h) use (&$h2) { if ($h['level'] !== 'h3') { $h2++; } return $h + ['n' => $h['level'] === 'h3' ? null : str_pad((string) $h2, 2, '0', STR_PAD_LEFT)]; });
                $tocList = function (bool $compact = false) use ($items, $post) {
                    return view('insights._toc', ['items' => $items, 'post' => $post, 'compact' => $compact])->render();
                };
            @endphp
            {{-- Laptops and up: a floating contents button with progress --}}
            <div class="fixed bottom-14 left-5 z-40 hidden lg:block" @keydown.escape.window="open = false" @click.outside="open = false">
                <div x-show="open" x-cloak x-transition.origin.bottom.left class="mb-3 w-[290px] overflow-hidden rounded-2xl border border-line bg-white shadow-xl">
                    <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                        <span class="relative grid size-10 shrink-0 place-items-center">
                            <svg viewBox="0 0 36 36" class="absolute inset-0 size-full -rotate-90"><circle cx="18" cy="18" r="15.5" fill="none" stroke="#ececef" stroke-width="3"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $hex }}" stroke-width="3" stroke-linecap="round" stroke-dasharray="97.4" :stroke-dashoffset="97.4 - 97.4 * pct / 100" class="transition-[stroke-dashoffset] duration-200"/></svg>
                            <span class="text-[10px] font-bold tabular-nums" x-text="pct + '%'">0%</span>
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span class="block text-[13px] font-semibold">On this page</span>
                            <span class="block text-[11px] text-muted" x-text="pct >= 98 ? 'Finished — thanks for reading' : left + ' min left'">{{ $post->reading_minutes }} min read</span>
                        </span>
                    </div>
                    {!! $tocList() !!}
                    <button type="button" @click="open = false; window.scrollTo({ top: 0, behavior: 'smooth' })" class="flex w-full items-center justify-center gap-1.5 border-t border-line py-2.5 text-xs font-medium text-muted transition hover:bg-zinc-50 hover:text-ink"><x-icon name="chevron-down" class="size-3.5 rotate-180"/> Back to top</button>
                </div>
                <button type="button" @click="open = !open" :aria-expanded="open" class="flex items-center gap-2.5 rounded-full border border-line bg-white py-1.5 pl-1.5 pr-4 text-[13px] font-medium shadow-[0_12px_30px_-14px_rgba(12,12,16,.45)] transition hover:border-zinc-300">
                    <span class="relative grid size-8 place-items-center">
                        <svg viewBox="0 0 36 36" class="absolute inset-0 size-full -rotate-90"><circle cx="18" cy="18" r="15.5" fill="none" stroke="#ececef" stroke-width="3.5"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $hex }}" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="97.4" :stroke-dashoffset="97.4 - 97.4 * pct / 100"/></svg>
                        <x-icon name="list" class="size-3.5"/>
                    </span>
                    <span class="max-w-[200px] truncate" x-text="index >= 0 ? @js($items->pluck('text')->all())[index] : 'On this page'">On this page</span>
                </button>
            </div>

            {{-- Phones & tablets: a collapsible box at the top --}}
            <details class="group mx-auto mb-6 max-w-[720px] px-5 lg:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl border border-line bg-white px-4 py-3 text-sm font-semibold [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center gap-2"><x-icon name="list" class="size-4 text-muted"/> On this page <span class="font-normal text-muted">· {{ $items->whereNotNull('n')->count() }} sections</span></span>
                    <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180"/>
                </summary>
                <div class="mt-2 overflow-hidden rounded-2xl border border-line bg-white">{!! $tocList(true) !!}</div>
            </details>
        @endif

        @forelse($post->blocks as $block)
            @include('insights._block', ['block' => $block, 'post' => $post])
        @empty
            <p class="mx-auto max-w-[720px] px-5 text-muted">This post has no content yet.</p>
        @endforelse
    </div>

    {{-- ───────── Footer: topics, share, author ───────── --}}
    <footer class="mx-auto max-w-[720px] px-5 pb-12">
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($post->topics ?? [] as $t)<a href="{{ route('insights.index', ['topic' => $t]) }}" class="chip hover:border-ink">{{ $t }}</a>@endforeach
            </div>
            @if($post->shows('share') && $post->isPublic())
                <x-share :url="$url" :title="$post->title" :image="$post->coverUrl()" :subtitle="$post->typeLabel().' · '.config('marketplace.brand').' Insights'" :pill="true"/>
            @endif
        </div>

    </footer>
</article>

{{-- ───────── Related ───────── --}}
@if($related->isNotEmpty())
    <section class="border-t border-line bg-[#fbfbfd] py-14">
        <div class="mx-auto max-w-[1120px] px-5">
            <div class="mb-8 flex items-end justify-between gap-3">
                <h2 class="font-display text-2xl font-bold tracking-tight sm:text-[28px]">Keep reading</h2>
                <a href="{{ route('insights.index') }}" class="text-sm font-semibold hover:underline">All Insights</a>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($related as $r)<x-post-card :post="$r" size="compact"/>@endforeach
            </div>
        </div>
    </section>
@endif

@if($guest)
    <section class="px-3 pb-3 sm:px-4">
        <div class="relative mx-auto max-w-[1320px] overflow-hidden rounded-[28px] bg-ink px-6 py-14 text-center text-white sm:px-12">
            <div class="pointer-events-none absolute -left-24 -top-24 size-80 rounded-full opacity-30 blur-3xl" style="background: {{ $hex }}"></div>
            <h2 class="relative font-display text-3xl font-bold tracking-tight sm:text-4xl">Where Ethiopian creative work gets discovered</h2>
            <p class="relative mx-auto mt-3 max-w-xl text-white/65">Free portfolios for creatives. Clear agreements for clients. No commission on project payments.</p>
            <div class="relative mt-7 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register', ['role' => 'freelancer']) }}" class="btn btn-pill h-12 bg-white px-6 text-ink hover:bg-zinc-100">Create your portfolio</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-pill h-12 border border-white/25 px-6 text-white hover:bg-white/10">Post a job</a>
            </div>
        </div>
    </section>
@endif
</x-dynamic-component>
