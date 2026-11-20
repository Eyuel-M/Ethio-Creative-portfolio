@props(['profile', 'shortlisted' => false])
{{-- Talent card that leads with the creative's work: a swipeable strip of their published project thumbnails. --}}
@php
    $rating = $profile->rating();
    $projects = $profile->publishedProjects->filter(fn ($p) => $p->coverUrl())->values();
    $count = $projects->count();
@endphp
<article class="card group/card flex flex-col overflow-hidden transition hover:shadow-[0_10px_30px_-18px_rgba(12,12,16,.35)]">
    {{-- Project carousel --}}
    <div class="relative aspect-[16/11] overflow-hidden bg-zinc-100"
         x-data="{ i: 0, n: {{ $count }}, sx: null,
                   go(d) { this.i = Math.max(0, Math.min(this.n - 1, this.i + d)) },
                   start(e) { this.sx = e.touches[0].clientX },
                   end(e) { if (this.sx === null) return; const dx = e.changedTouches[0].clientX - this.sx; if (Math.abs(dx) > 40) this.go(dx < 0 ? 1 : -1); this.sx = null } }"
         @touchstart.passive="start($event)" @touchend.passive="end($event)"
         @keydown.arrow-right.prevent="go(1)" @keydown.arrow-left.prevent="go(-1)"
         role="region" aria-roledescription="carousel" aria-label="Work by {{ $profile->user->displayName() }}">
        @if($count)
            <div class="flex h-full transition-transform duration-300 ease-out" :style="`transform: translateX(-${i * 100}%)`">
                @foreach($projects as $k => $p)
                    <a href="{{ route('portfolio.show', $p) }}" class="relative block h-full w-full shrink-0" aria-label="{{ $p->title }} ({{ $k + 1 }} of {{ $count }})" @if($k) tabindex="-1" :tabindex="i === {{ $k }} ? 0 : -1" @endif>
                        <img src="{{ $p->coverUrl() }}" alt="{{ $p->cover_alt ?? $p->title }}" class="size-full object-cover" loading="lazy">
                        <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/70 to-transparent px-3.5 pb-6 pt-10 text-sm font-semibold text-white">{{ $p->title }}</span>
                    </a>
                @endforeach
            </div>
            @if($count > 1)
                <button type="button" @click="go(-1)" x-show="i > 0" x-cloak class="absolute left-2.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow-md transition hover:scale-105 sm:opacity-0 sm:group-hover/card:opacity-100 sm:focus:opacity-100" aria-label="Previous project"><x-icon name="chevron-left" class="size-5"/></button>
                <button type="button" @click="go(1)" x-show="i < n - 1" class="absolute right-2.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow-md transition hover:scale-105 sm:opacity-0 sm:group-hover/card:opacity-100 sm:focus:opacity-100" aria-label="Next project"><x-icon name="chevron-right" class="size-5"/></button>
                <span class="absolute right-2.5 top-2.5 rounded-full bg-black/55 px-2 py-0.5 text-[11px] font-medium text-white backdrop-blur"><span x-text="i + 1">1</span> / {{ $count }}</span>
                <div class="absolute inset-x-0 bottom-1.5 flex justify-center gap-1">
                    @foreach($projects as $k => $p)
                        <button type="button" @click="i = {{ $k }}" class="h-1.5 rounded-full transition-all" :class="i === {{ $k }} ? 'w-4 bg-white' : 'w-1.5 bg-white/55'" aria-label="Show project {{ $k + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        @else
            <div class="grid size-full place-items-center bg-gradient-to-br from-indigo-50 via-violet-50 to-sky-50 text-center">
                <div><x-icon name="image" class="mx-auto size-8 text-faint"/><p class="mt-2 text-sm text-muted">No published work yet</p></div>
            </div>
        @endif
        @if($profile->isFeatured())
            <span class="absolute left-2.5 top-2.5 rounded-full bg-white/90 px-2.5 py-0.5 text-[11px] font-semibold text-violet-700">Sponsored</span>
        @endif
    </div>

    {{-- Creative --}}
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-start gap-3">
            <x-avatar :user="$profile->user" size="size-12" class="text-sm"/>
            <div class="min-w-0 flex-1">
                <h3 class="truncate font-display text-lg font-bold leading-tight"><a href="{{ route('freelancers.show', $profile) }}" class="hover:underline">{{ $profile->user->displayName() }}</a></h3>
                <p class="truncate text-sm text-muted">{{ $profile->title ?? 'Creative freelancer' }}</p>
            </div>
            <span class="pill shrink-0 {{ \App\Support\Labels::tone($profile->availability) }}">{{ ucfirst($profile->availability) }}</span>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-ink-soft">
            @if($profile->location)<span class="meta"><x-icon name="pin" class="size-4"/>{{ $profile->location }}</span>@endif
            <span class="meta"><x-icon name="star" class="size-4 {{ $rating['count'] ? 'fill-amber-400 text-amber-400' : '' }}"/>{{ $rating['count'] ? $rating['avg'].' ('.$rating['count'].')' : 'New' }}</span>
            <span class="meta"><x-icon name="layers" class="size-4"/>{{ $profile->published_projects_count ?? $count }} projects</span>
        </div>
        @if($profile->skills->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-1.5">@foreach($profile->skills->take(4) as $s)<span class="chip">{{ $s->name }}</span>@endforeach</div>
        @endif
        <div class="mt-auto flex items-center justify-between gap-2 pt-5">
            <span class="whitespace-nowrap text-sm"><span class="font-semibold">{{ $profile->hourly_rate_minor ? \App\Support\Money::format($profile->hourly_rate_minor, $profile->currency) : '—' }}</span><span class="text-muted"> /hour</span></span>
            <div class="flex items-center gap-2">
                @auth
                    @if(auth()->user()->is_client && auth()->id() !== $profile->user_id)
                        <form method="POST" action="{{ route('client.shortlist', $profile) }}">@csrf
                            <button class="btn btn-sm w-9 px-0 {{ $shortlisted ? 'btn-dark' : 'btn-light' }}" aria-pressed="{{ $shortlisted ? 'true' : 'false' }}" aria-label="{{ $shortlisted ? 'Remove from saved' : 'Save' }}"><x-icon name="bookmark" class="size-4"/></button>
                        </form>
                    @endif
                @endauth
                <a href="{{ route('freelancers.show', $profile) }}" class="btn btn-sm btn-dark">View portfolio</a>
            </div>
        </div>
    </div>
</article>
