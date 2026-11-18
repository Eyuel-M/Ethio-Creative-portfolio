@php
    $author = $project->profile;
    $likeCount = $project->likes()->count();
    $likedByMe = in_array($project->id, $likedIds ?? [], true);
    $canLike = auth()->check() && ! $isOwner && $project->isPublished();
@endphp
<x-dynamic-component :component="auth()->check() ? 'layouts.discover' : 'layouts.public'" :title="$project->title">
@push('meta')
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $project->title }} — {{ $author->user->displayName() }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($project->summary ?? $author->title ?? 'Creative portfolio project', 200) }}">
<meta property="og:url" content="{{ route('portfolio.show', $project) }}">
@if($project->coverUrl())<meta property="og:image" content="{{ $project->coverUrl() }}"><meta name="twitter:card" content="summary_large_image">@endif
@endpush

{{-- Full-screen project view: work spans the whole width; text stays in a readable column. --}}
<article class="bg-white" x-data="{ liked: @js($likedByMe), count: {{ $likeCount }},
        async toggle() {
            const r = await fetch(@js(route('portfolio.like', $project)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            if (r.ok) { const d = await r.json(); this.liked = d.liked; this.count = d.count; }
        } }">
    @if(! $project->isPublished())
        <div class="bg-amber-50 px-6 py-2.5 text-center text-sm font-medium text-amber-800">Draft preview — only you can see this page.</div>
    @endif

    {{-- Project header --}}
    <header class="mx-auto max-w-[1600px] px-4 pb-8 pt-8 sm:px-6 lg:px-10 lg:pt-10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="flex min-w-0 items-start gap-4">
                <a href="{{ route('freelancers.show', $author) }}" class="shrink-0"><x-avatar :user="$author->user" size="size-14" class="text-base"/></a>
                <div class="min-w-0">
                    <h1 class="font-display text-3xl font-bold leading-tight tracking-tight sm:text-5xl">{{ $project->title }}</h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[15px] text-ink-soft">
                        <a href="{{ route('freelancers.show', $author) }}" class="font-semibold text-ink hover:underline">{{ $author->user->displayName() }}</a>
                        @if($author->title)<span class="text-faint">·</span><span>{{ $author->title }}</span>@endif
                        @if($project->category)<span class="text-faint">·</span><a href="{{ route(auth()->check() ? 'home' : 'explore', ['category' => $project->category->slug]) }}" class="hover:text-ink">{{ $project->category->name }}</a>@endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($canLike)
                    <button type="button" @click="toggle()" :aria-pressed="liked" class="btn btn-pill h-11 px-5" :class="liked ? 'bg-rose-500 text-white hover:bg-rose-600' : 'btn-dark'">
                        <x-icon name="heart" class="size-5" ::class="liked && 'fill-white'"/> <span x-text="liked ? 'Appreciated' : 'Appreciate'">Appreciate</span>
                    </button>
                @elseif(! auth()->check() && $project->isPublished())
                    <a href="{{ route('login') }}" class="btn btn-dark btn-pill h-11 px-5"><x-icon name="heart" class="size-5"/> Appreciate</a>
                @endif
                @if($project->isPublished())
                    <x-share :url="route('portfolio.show', $project)" :title="$project->title" :image="$project->coverUrl()" :subtitle="'by '.$author->user->displayName()"/>
                @endif
                @if($isOwner)
                    <a href="{{ route('portfolio.edit', $project) }}" class="btn btn-light"><x-icon name="pen" class="size-4"/> Edit</a>
                @endif
            </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
            <span class="flex items-center gap-1.5"><x-icon name="heart" class="size-4"/><span x-text="count">{{ $likeCount }}</span> likes</span>
            <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-4"/>{{ number_format($project->views) }} views</span>
            @if($project->project_date)<span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4"/>{{ $project->project_date->format('F Y') }}</span>@endif
            @if($project->client_attribution)<span class="flex items-center gap-1.5"><x-icon name="briefcase" class="size-4"/>Client: {{ $project->client_attribution }}</span>@endif
        </div>
        @if($project->summary)
            <p class="mt-5 max-w-3xl text-lg leading-relaxed text-ink-soft">{{ $project->summary }}</p>
        @endif
    </header>

    {{-- The work: edge to edge --}}
    <div class="bg-white">
        @if($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="{{ $project->cover_alt ?? $project->title }}" class="block h-auto w-full">
        @endif
        @foreach($project->blocks as $block)
            @if($block->type === 'text')
                <div class="mx-auto max-w-3xl whitespace-pre-line px-5 py-14 text-lg leading-relaxed text-ink-soft first-line:font-display first-line:text-2xl first-line:font-bold first-line:text-ink sm:py-20">{{ $block->body }}</div>
            @elseif($block->type === 'image' && $block->mediaUrl())
                <figure>
                    <img src="{{ $block->mediaUrl() }}" alt="{{ $block->alt ?? '' }}" class="block h-auto w-full" loading="lazy">
                    @if($block->caption)<figcaption class="mx-auto max-w-[1600px] px-4 py-3 text-sm text-muted sm:px-6 lg:px-10">{{ $block->caption }}</figcaption>@endif
                </figure>
            @elseif($block->type === 'embed' && $block->embedSrc())
                <figure>
                    <div class="aspect-video w-full bg-black"><iframe src="{{ $block->embedSrc() }}" class="size-full" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" title="{{ $block->caption ?? 'Video' }}" sandbox="allow-scripts allow-same-origin allow-presentation"></iframe></div>
                    @if($block->caption)<figcaption class="mx-auto max-w-[1600px] px-4 py-3 text-sm text-muted sm:px-6 lg:px-10">{{ $block->caption }}</figcaption>@endif
                </figure>
            @endif
        @endforeach
    </div>

    {{-- Closing: creator + actions + tags --}}
    <footer class="border-b border-line px-4 py-16 text-center sm:px-6">
        <a href="{{ route('freelancers.show', $author) }}" class="inline-block"><x-avatar :user="$author->user" size="size-20" class="text-xl ring-4 ring-white shadow-md"/></a>
        <p class="mt-4 font-display text-2xl font-bold">{{ $author->user->displayName() }}</p>
        @if($author->title)<p class="mt-1 text-ink-soft">{{ $author->title }}</p>@endif
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @if($canLike)
                <button type="button" @click="toggle()" :aria-pressed="liked" class="btn btn-pill h-12 px-6" :class="liked ? 'bg-rose-500 text-white hover:bg-rose-600' : 'btn-dark'">
                    <x-icon name="heart" class="size-5" ::class="liked && 'fill-white'"/> <span x-text="liked ? 'Appreciated' : 'Appreciate'">Appreciate</span> · <span x-text="count">{{ $likeCount }}</span>
                </button>
            @elseif(! auth()->check() && $project->isPublished())
                <a href="{{ route('login') }}" class="btn btn-dark btn-pill h-12 px-6"><x-icon name="heart" class="size-5"/> Appreciate · {{ $likeCount }}</a>
            @endif
            @if($project->isPublished())
                <x-share :url="route('portfolio.show', $project)" :title="$project->title" :image="$project->coverUrl()" :subtitle="'by '.$author->user->displayName()"/>
            @endif
            <a href="{{ route('freelancers.show', $author) }}" class="btn btn-light btn-pill h-12 px-6">View profile</a>
        </div>
        @if($project->tags)
            <div class="mx-auto mt-8 flex max-w-2xl flex-wrap justify-center gap-2">
                @foreach($project->tags as $t)<span class="chip">{{ $t }}</span>@endforeach
            </div>
        @endif
    </footer>
</article>

{{-- More by the creator: full width, matching the work above --}}
@if($more->isNotEmpty())
<section class="mx-auto max-w-[1600px] px-4 py-14 sm:px-6 lg:px-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-avatar :user="$author->user" size="size-11" class="text-sm"/>
            <div><p class="text-sm text-muted">More by</p><h2 class="font-display text-2xl font-bold leading-tight">{{ $author->user->displayName() }}</h2></div>
        </div>
        <a href="{{ route('freelancers.show', $author) }}" class="btn btn-light btn-pill">View full portfolio <x-icon name="arrow-right" class="size-4"/></a>
    </div>
    @php $cols = match (min($more->count(), 4)) { 1 => 'sm:grid-cols-2', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3', default => 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' }; @endphp
    <div class="mt-8 grid gap-x-6 gap-y-8 {{ $cols }}">
        @foreach($more as $m)
            <x-feed-card :project="$m->setRelation('profile', $author)" :liked="in_array($m->id, $likedIds ?? [], true)"/>
        @endforeach
    </div>
</section>
@endif
</x-dynamic-component>
