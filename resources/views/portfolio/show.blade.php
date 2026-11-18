<x-shell :title="$project->title">
@php $author = $project->profile; @endphp
<article class="card overflow-hidden">
    @if(! $project->isPublished())<div class="bg-amber-50 px-6 py-2.5 text-center text-sm text-amber-800">Draft preview — only you can see this page.</div>@endif
    <header class="mx-auto max-w-3xl px-5 pb-8 pt-10 text-center sm:pt-14">
        @if($project->category)<span class="eyebrow">{{ $project->category->name }}</span>@endif
        <h1 class="mt-4 font-display text-4xl font-bold tracking-tight sm:text-5xl">{{ $project->title }}</h1>
        @if($project->summary)<p class="mx-auto mt-4 max-w-2xl text-lg text-ink-soft">{{ $project->summary }}</p>@endif
        <a href="{{ route('freelancers.show', $author) }}" class="mt-6 inline-flex items-center gap-3 rounded-full border border-line py-1.5 pl-1.5 pr-4 hover:bg-zinc-50">
            <x-avatar :user="$author->user" size="size-9" class="text-xs"/><span class="text-left text-sm"><span class="block font-semibold">{{ $author->user->name }}</span><span class="block text-xs text-muted">{{ $author->title }}</span></span>
        </a>
        <div class="mt-5 flex flex-wrap justify-center gap-x-4 gap-y-1 text-sm text-muted">
            @if($project->project_date)<span>{{ $project->project_date->format('F Y') }}</span>@endif
            @if($project->client_attribution)<span>Client: {{ $project->client_attribution }}</span>@endif
            <span class="flex items-center gap-1"><x-icon name="eye" class="size-4"/>{{ number_format($project->views) }} views</span>
        </div>
        @php $likeCount = $project->likes()->count(); $likedByMe = auth()->check() && $project->likes()->whereKey(auth()->id())->exists(); @endphp
        @if(auth()->check() && ! $isOwner && $project->isPublished())
            <div class="mt-6" x-data="{ liked: @js($likedByMe), count: {{ $likeCount }},
                async toggle() { const r = await fetch(@js(route('portfolio.like', $project)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }); if (r.ok) { const d = await r.json(); this.liked = d.liked; this.count = d.count; } } }">
                <button type="button" @click="toggle()" :aria-pressed="liked" class="btn btn-pill h-12 px-6" :class="liked ? 'bg-rose-500 text-white hover:bg-rose-600' : 'btn-dark'">
                    <x-icon name="heart" class="size-5" ::class="liked && 'fill-white'"/> <span x-text="liked ? 'Liked' : 'Appreciate'"></span> · <span x-text="count"></span>
                </button>
            </div>
        @else
            <p class="mt-4 flex items-center justify-center gap-1 text-sm text-muted"><x-icon name="heart" class="size-4"/>{{ $likeCount }} likes</p>
        @endif
    </header>
    @if($project->coverUrl())
        <img src="{{ $project->coverUrl() }}" alt="{{ $project->cover_alt ?? $project->title }}" class="mx-auto w-full max-w-5xl px-4 sm:px-6">
    @endif
    <div class="mx-auto max-w-3xl space-y-10 px-5 py-12">
        @foreach($project->blocks as $block)
            @if($block->type === 'text')
                <div class="whitespace-pre-line text-[17px] leading-relaxed text-ink-soft">{{ $block->body }}</div>
            @elseif($block->type === 'image')
                <figure class="-mx-5 sm:-mx-16">
                    <img src="{{ $block->mediaUrl() }}" alt="{{ $block->alt ?? '' }}" class="w-full rounded-2xl" loading="lazy">
                    @if($block->caption)<figcaption class="mt-3 text-center text-sm text-muted">{{ $block->caption }}</figcaption>@endif
                </figure>
            @elseif($block->type === 'embed' && $block->embedSrc())
                <figure class="-mx-5 sm:-mx-16">
                    <div class="aspect-video overflow-hidden rounded-2xl bg-black"><iframe src="{{ $block->embedSrc() }}" class="size-full" allow="fullscreen; picture-in-picture" loading="lazy" title="{{ $block->caption ?? 'Video' }}" sandbox="allow-scripts allow-same-origin allow-presentation"></iframe></div>
                    @if($block->caption)<figcaption class="mt-3 text-center text-sm text-muted">{{ $block->caption }}</figcaption>@endif
                </figure>
            @endif
        @endforeach
        @if($project->tags)
            <div class="flex flex-wrap justify-center gap-2 border-t border-line pt-8">@foreach($project->tags as $t)<span class="chip">{{ $t }}</span>@endforeach</div>
        @endif
    </div>
</article>
@if($more->isNotEmpty())
<section class="card card-pad">
    <div class="flex items-center justify-between"><h2 class="panel-title">More by {{ $author->user->name }}</h2><a href="{{ route('freelancers.show', $author) }}" class="text-sm font-medium underline">View profile</a></div>
    <div class="mt-5 grid gap-4 sm:grid-cols-3">@foreach($more as $m)<x-portfolio-tile :project="$m->setRelation('profile', $author)" :showAuthor="false"/>@endforeach</div>
</section>
@endif
</x-shell>
