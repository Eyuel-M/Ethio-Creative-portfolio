@props(['project', 'rating' => null, 'liked' => false, 'size' => 'grid'])
@php
    $author = $project->profile;
    $mine = auth()->id() === $author->user_id || (bool) auth()->user()?->inAdminMode();
    $overlay = in_array($size, ['large', 'tile'], true);
    $fmt = fn ($n) => $n >= 1000 ? round($n / 1000, 1).'K' : $n;
@endphp
<article {{ $attributes->merge(['class' => 'group flex min-w-0 flex-col']) }}
         x-data="{ liked: @js($liked), count: {{ (int) ($project->likes_count ?? 0) }}, busy: false,
                   async toggle() {
                       if (this.busy) return; this.busy = true;
                       try {
                           const r = await fetch(@js(route('portfolio.like', $project)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                           if (r.status === 401) { window.location = @js(route('login')); return; }
                           if (r.ok) { const d = await r.json(); this.liked = d.liked; this.count = d.count; }
                       } finally { this.busy = false; }
                   } }">
    <div class="relative flex-1 overflow-hidden bg-zinc-900 {{ $overlay ? 'min-h-[320px] rounded-2xl' : 'aspect-[4/3] rounded-lg' }}">
        @if($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="{{ $project->cover_alt ?? $project->title }}" class="absolute inset-0 size-full object-cover transition duration-500 {{ $overlay ? 'group-hover:scale-[1.04]' : '' }}" loading="lazy">
        @else
            <div class="absolute inset-0 grid place-items-center text-zinc-500"><x-icon name="image" class="size-10"/></div>
        @endif
        <a href="{{ route('portfolio.show', $project) }}" class="absolute inset-0 z-[1]" aria-label="{{ $project->title }} by {{ $author->user->displayName() }}"></a>

        @if($overlay)
            {{-- Bento tile: always-on overlay with rating, badges, title and author --}}
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/15"></div>
            <div class="absolute left-3 top-3 z-[2] flex items-center gap-2">
                @unless($mine)
                    <button type="button" @click="toggle()" :aria-pressed="liked" class="grid size-9 place-items-center rounded-full bg-white/95 text-ink shadow-sm transition hover:scale-105" :aria-label="liked ? 'Unlike' : 'Like'">
                        <x-icon name="heart" class="size-[18px]" ::class="liked && 'fill-rose-500 text-rose-500'"/>
                    </button>
                @endunless
                @if($rating)<span class="flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{{ number_format($rating, 1) }} <x-icon name="star" class="size-3.5 fill-amber-400 text-amber-400"/></span>@endif
            </div>
            <div class="absolute right-3 top-3 z-[2] flex gap-1.5">
                @if($author->isFeatured())<span class="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-violet-700">Sponsored</span>@endif
                @if($author->plan === 'professional')<span class="rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">Pro</span>@endif
            </div>
            <div class="pointer-events-none absolute bottom-14 left-4 right-4 z-[2] text-white">
                @if($project->category)<span class="text-xs font-medium uppercase tracking-wider text-white/75">{{ $project->category->name }}</span>@endif
                <p class="font-display font-bold leading-tight drop-shadow {{ $size === 'large' ? 'text-2xl sm:text-3xl' : 'line-clamp-2 text-lg' }}">{{ $project->title }}</p>
            </div>
            <a href="{{ route('freelancers.show', $author) }}" class="absolute bottom-3 left-3 z-[2] flex max-w-[80%] items-center gap-2 rounded-full py-1 pl-1 pr-3 text-white transition hover:bg-white/15">
                <x-avatar :user="$author->user" size="size-8" class="text-[11px] ring-2 ring-white/80"/>
                <span class="truncate text-sm font-medium drop-shadow">{{ $author->user->displayName() }}</span>
            </a>
        @else
            {{-- Grid card: clean image; title + like appear on hover (like Behance) --}}
            @if($author->isFeatured())<span class="absolute left-2.5 top-2.5 z-[2] rounded-md bg-white/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">Sponsored</span>@endif
            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[2] flex items-end justify-between gap-3 bg-gradient-to-t from-black/70 to-transparent p-3 pt-12 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                <p class="line-clamp-2 font-semibold leading-snug text-white">{{ $project->title }}</p>
                @unless($mine)
                    <button type="button" @click="toggle()" :aria-pressed="liked" class="pointer-events-auto grid size-9 shrink-0 place-items-center rounded-full bg-white text-ink shadow transition hover:scale-105" :aria-label="liked ? 'Unlike' : 'Like'">
                        <x-icon name="heart" class="size-[18px]" ::class="liked && 'fill-rose-500 text-rose-500'"/>
                    </button>
                @endunless
            </div>
        @endif
    </div>

    @unless($overlay)
        <div class="flex items-center justify-between gap-3 pt-2.5">
            <a href="{{ route('freelancers.show', $author) }}" class="flex min-w-0 items-center gap-2 text-sm font-semibold hover:underline">
                <x-avatar :user="$author->user" size="size-6" class="text-[9px]"/>
                <span class="truncate">{{ $author->user->displayName() }}</span>
                @if($author->plan === 'professional')<span class="shrink-0 rounded bg-ink px-1 py-px text-[9px] font-bold uppercase tracking-wide text-white">Pro</span>@endif
            </a>
            <div class="flex shrink-0 items-center gap-3 text-xs font-medium text-ink-soft">
                <button type="button" @if(! $mine) @click="toggle()" @endif class="flex items-center gap-1 {{ $mine ? 'cursor-default' : 'hover:text-ink' }}" title="Likes" :aria-pressed="liked" @if($mine) disabled @endif>
                    <x-icon name="heart" class="size-4" ::class="liked && 'fill-rose-500 text-rose-500'"/><span x-text="count">{{ (int) ($project->likes_count ?? 0) }}</span>
                </button>
                <span class="flex items-center gap-1" title="Views"><x-icon name="eye" class="size-4"/>{{ $fmt($project->views) }}</span>
            </div>
        </div>
    @endunless
</article>
