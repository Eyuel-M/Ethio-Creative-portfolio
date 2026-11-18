@props(['project', 'rating' => null, 'liked' => false, 'size' => 'normal'])
@php
    $author = $project->profile;
    $mine = auth()->id() === $author->user_id;
    $big = in_array($size, ['large', 'tile'], true);
@endphp
<article {{ $attributes->merge(['class' => 'group flex min-w-0 flex-col']) }}
         x-data="{ liked: @js($liked), count: {{ (int) ($project->likes_count ?? 0) }}, busy: false,
                   async toggle() {
                       if (this.busy) return; this.busy = true;
                       try {
                           const r = await fetch(@js(route('portfolio.like', $project)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                           if (r.ok) { const d = await r.json(); this.liked = d.liked; this.count = d.count; }
                       } finally { this.busy = false; }
                   } }">
    <div class="relative flex-1 overflow-hidden rounded-2xl bg-zinc-900 {{ $big ? 'min-h-[320px]' : 'aspect-[4/3]' }}">
        @if($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="{{ $project->cover_alt ?? $project->title }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.04]" loading="lazy">
        @else
            <div class="absolute inset-0 grid place-items-center text-zinc-500"><x-icon name="image" class="size-10"/></div>
        @endif
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-black/15"></div>
        <a href="{{ route('portfolio.show', $project) }}" class="absolute inset-0 z-[1]" aria-label="{{ $project->title }} by {{ $author->user->name }}"></a>

        {{-- top-left: like + rating --}}
        <div class="absolute left-3 top-3 z-[2] flex items-center gap-2">
            @unless($mine)
                <button type="button" @click="toggle()" :aria-pressed="liked" class="grid size-9 place-items-center rounded-full bg-white/95 text-ink shadow-sm transition hover:scale-105" :class="liked && 'text-rose-500'" :aria-label="liked ? 'Unlike' : 'Like'">
                    <x-icon name="heart" class="size-[18px]" ::class="liked && 'fill-rose-500'"/>
                </button>
            @endunless
            @if($rating)
                <span class="flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{{ number_format($rating, 1) }} <x-icon name="star" class="size-3.5 fill-amber-400 text-amber-400"/></span>
            @endif
        </div>

        {{-- top-right: plan / featured badge --}}
        <div class="absolute right-3 top-3 z-[2] flex gap-1.5">
            @if($author->isFeatured())<span class="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-violet-700" title="Paid promotional placement">Sponsored</span>@endif
            @if($author->plan === 'professional')<span class="rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">Pro</span>@endif
        </div>

        {{-- bottom: author --}}
        <a href="{{ route('freelancers.show', $author) }}" class="absolute bottom-3 left-3 z-[2] flex max-w-[80%] items-center gap-2 rounded-full py-1 pl-1 pr-3 text-white transition hover:bg-white/15">
            <x-avatar :user="$author->user" size="size-8" class="text-[11px] ring-2 ring-white/80"/>
            <span class="truncate text-sm font-medium drop-shadow">{{ $author->user->name }}</span>
        </a>
        @if($big)
            <div class="pointer-events-none absolute bottom-14 left-4 right-4 z-[2] text-white">
                @if($project->category)<span class="text-xs font-medium uppercase tracking-wider text-white/75">{{ $project->category->name }}</span>@endif
                <p class="font-display font-bold leading-tight drop-shadow {{ $size === 'large' ? 'text-2xl sm:text-3xl' : 'line-clamp-2 text-lg' }}">{{ $project->title }}</p>
            </div>
        @endif
    </div>
    @unless($big)
        <div class="flex items-start justify-between gap-3 px-1 pt-2.5">
            <a href="{{ route('portfolio.show', $project) }}" class="min-w-0 text-[15px] font-semibold leading-snug hover:underline"><span class="line-clamp-1">{{ $project->title }}</span></a>
            <div class="flex shrink-0 items-center gap-3 text-xs text-muted">
                <span class="flex items-center gap-1" title="Likes"><x-icon name="heart" class="size-3.5"/><span x-text="count"></span></span>
                <span class="flex items-center gap-1" title="Views"><x-icon name="eye" class="size-3.5"/>{{ $project->views >= 1000 ? round($project->views / 1000, 1).'k' : $project->views }}</span>
            </div>
        </div>
    @endunless
</article>
