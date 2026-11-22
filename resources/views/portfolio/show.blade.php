@php
    $author = $project->profile;
    $likeCount = $project->likes()->count();
    $likedByMe = in_array($project->id, $likedIds ?? [], true);
    $staff = (bool) auth()->user()?->inAdminMode();
    $canLike = auth()->check() && ! $isOwner && ! $staff && $project->isPublished();
    $savedByMe = (bool) auth()->user()?->hasSavedProject($project->id);
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
<article class="bg-white" x-data="{ liked: @js($likedByMe), saved: @js($savedByMe), count: {{ $likeCount }},
        async save() {
            const r = await fetch(@js(route('portfolio.save', $project)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            if (r.ok) { this.saved = (await r.json()).saved; }
        },
        async toggle() {
            const r = await fetch(@js(route('portfolio.like', $project)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            if (r.ok) { const d = await r.json(); this.liked = d.liked; this.count = d.count; }
        } }">
    @if(! $project->isPublished())
        <div class="bg-amber-50 px-6 py-2.5 text-center text-sm font-medium text-amber-800">Draft preview — only you can see this page.</div>
    @endif

    {{-- Project header --}}
    <header class="mx-auto max-w-[1600px] px-4 pb-10 pt-10 sm:px-6 lg:px-10 lg:pt-14">
        {{-- Label: category · date · client --}}
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold uppercase tracking-[0.14em] text-muted">
            @if($project->category)
                <a href="{{ route(auth()->check() ? 'home' : 'explore', ['category' => $project->category->slug]) }}" class="text-brand hover:underline">{{ $project->category->name }}</a>
            @endif
            @if($project->project_date)<span class="text-faint" aria-hidden="true">·</span><span>{{ $project->project_date->format('F Y') }}</span>@endif
            @if($project->client_attribution)<span class="text-faint" aria-hidden="true">·</span><span>Client: {{ $project->client_attribution }}</span>@endif
        </p>

        {{-- Title + summary share one left edge --}}
        <h1 class="mt-4 max-w-5xl font-display text-4xl font-bold leading-[1.05] tracking-tight sm:text-6xl">{{ $project->title }}</h1>
        @if($project->summary)
            <p class="mt-5 max-w-3xl text-lg leading-relaxed text-ink-soft sm:text-xl">{{ $project->summary }}</p>
        @endif

        {{-- Byline bar: creator · stats · actions on one line --}}
        <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-5 border-t border-line pt-6">
            <a href="{{ route('freelancers.show', $author) }}" class="group flex min-w-0 items-center gap-3">
                <x-avatar :user="$author->user" size="size-12" class="text-sm"/>
                <span class="min-w-0">
                    <span class="block truncate font-semibold group-hover:underline">{{ $author->user->displayName() }}</span>
                    @if($author->title)<span class="block truncate text-sm text-muted">{{ $author->title }}</span>@endif
                </span>
            </a>

            <dl class="flex items-center gap-5 text-sm">
                <div class="flex items-center gap-1.5" title="Likes"><dt class="sr-only">Likes</dt><x-icon name="heart" class="size-4 text-muted" ::class="liked && 'fill-rose-500 text-rose-500'"/><dd class="font-semibold" x-text="count">{{ $likeCount }}</dd></div>
                <div class="flex items-center gap-1.5" title="Views"><dt class="sr-only">Views</dt><x-icon name="eye" class="size-4 text-muted"/><dd class="font-semibold">{{ number_format($project->views) }}</dd></div>
            </dl>

            <div class="flex w-full flex-wrap items-center gap-2 sm:ml-auto sm:w-auto">
                @if($canLike)
                    <button type="button" @click="toggle()" :aria-pressed="liked" class="btn btn-pill h-11 flex-1 px-5 sm:flex-none" :class="liked ? 'bg-rose-500 text-white hover:bg-rose-600' : 'btn-dark'">
                        <x-icon name="heart" class="size-5" ::class="liked && 'fill-white'"/> <span x-text="liked ? 'Appreciated' : 'Appreciate'">Appreciate</span>
                    </button>
                    <button type="button" @click="save()" :aria-pressed="saved" class="btn btn-pill h-11 px-5" :class="saved ? 'border border-brand bg-blue-50 text-brand hover:bg-blue-100' : 'btn-light'" title="Saves are private — only you can see them">
                        <x-icon name="bookmark" class="size-5" ::class="saved && 'fill-brand'"/> <span x-text="saved ? 'Saved' : 'Save'">Save</span>
                    </button>
                @elseif(! auth()->check() && $project->isPublished())
                    <a href="{{ route('login') }}" class="btn btn-dark btn-pill h-11 flex-1 px-5 sm:flex-none"><x-icon name="heart" class="size-5"/> Appreciate</a>
                    <a href="{{ route('login') }}" class="btn btn-light btn-pill h-11 px-5"><x-icon name="bookmark" class="size-5"/> Save</a>
                @endif
                @if($staff && auth()->user()->canAdmin('moderation'))
                    <a href="{{ route('admin.portfolio', ['q' => $project->id, 'status' => $project->status === 'removed' ? 'removed' : ($project->reviewed_at ? 'approved' : 'review')]) }}" class="btn btn-dark btn-pill h-11 px-5"><x-icon name="shield" class="size-4"/> Moderate in console</a>
                @endif
                @if($project->isPublished())
                    <x-share :url="route('portfolio.show', $project)" :title="$project->title" :image="$project->coverUrl()" :subtitle="'by '.$author->user->displayName()" :pill="true"/>
                @endif
                @if($isOwner)
                    <a href="{{ route('portfolio.edit', $project) }}" class="btn btn-light btn-pill h-11 px-5"><x-icon name="pen" class="size-4"/> Edit</a>
                @endif
            </div>
        </div>
    </header>

    {{-- The work: full width with an even 8px gutter around and between images --}}
    <div class="bg-white">
        @if($project->coverUrl())
            <div class="px-2 pb-1"><img src="{{ $project->coverUrl() }}" alt="{{ $project->cover_alt ?? $project->title }}" class="block h-auto w-full rounded-xl"></div>
        @endif
        @foreach($project->blocks as $block)
            @include('portfolio._block', ['block' => $block])
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
                <button type="button" @click="save()" :aria-pressed="saved" class="btn btn-pill h-12 px-6" :class="saved ? 'border border-brand bg-blue-50 text-brand hover:bg-blue-100' : 'btn-light'">
                    <x-icon name="bookmark" class="size-5" ::class="saved && 'fill-brand'"/> <span x-text="saved ? 'Saved' : 'Save'">Save</span>
                </button>
            @elseif(! auth()->check() && $project->isPublished())
                <a href="{{ route('login') }}" class="btn btn-dark btn-pill h-12 px-6"><x-icon name="heart" class="size-5"/> Appreciate · {{ $likeCount }}</a>
            @endif
            @if($project->isPublished())
                <x-share :url="route('portfolio.show', $project)" :title="$project->title" :image="$project->coverUrl()" :subtitle="'by '.$author->user->displayName()" :pill="true"/>
            @endif
            <a href="{{ route('freelancers.show', $author) }}" class="btn btn-light btn-pill h-12 px-6">View profile</a>
        </div>
        @if($project->tags)
            <div class="mx-auto mt-8 flex max-w-2xl flex-wrap justify-center gap-2">
                @foreach($project->tags as $t)<span class="chip">{{ $t }}</span>@endforeach
            </div>
        @endif
        @if(! $isOwner && $project->isPublished())<div class="mt-8"><x-report-button type="portfolio" :id="$project->id" label="Report this project"/></div>@endif
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
