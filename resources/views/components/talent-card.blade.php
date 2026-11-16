@props(['profile', 'shortlisted' => false])
@php $rating = $profile->rating(); $cover = $profile->publishedProjects->first()?->coverUrl(); @endphp
<article class="card group relative flex flex-col overflow-hidden transition hover:shadow-[0_10px_30px_-18px_rgba(12,12,16,.35)]">
    <div class="relative h-28 overflow-hidden bg-gradient-to-br from-indigo-100 via-violet-50 to-sky-100">
        @if($cover)<img src="{{ $cover }}" alt="" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">@endif
        @if($profile->isFeatured())
            <span class="absolute left-3 top-3 pill bg-white/90 text-violet-700 ring-violet-600/15 backdrop-blur" title="Paid promotional placement">Sponsored</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="-mt-12 flex items-end justify-between">
            <x-avatar :user="$profile->user" size="size-16" class="text-lg ring-4 ring-white"/>
            <span class="pill {{ \App\Support\Labels::tone($profile->availability) }}">{{ ucfirst($profile->availability) }}</span>
        </div>
        <h3 class="mt-3 font-display text-lg font-bold">
            <a href="{{ route('freelancers.show', $profile) }}" class="after:absolute after:inset-0 after:content-['']">{{ $profile->user->name }}</a>
        </h3>
        <p class="text-sm text-muted">{{ $profile->title ?? 'Creative freelancer' }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-ink-soft">
            @if($profile->location)<span class="meta"><x-icon name="pin" class="size-4"/>{{ $profile->location }}</span>@endif
            <span class="meta"><x-icon name="star" class="size-4 {{ $rating['count'] ? 'fill-amber-400 text-amber-400' : '' }}"/>{{ $rating['count'] ? $rating['avg'].' ('.$rating['count'].')' : 'New' }}</span>
            <span class="meta"><x-icon name="layers" class="size-4"/>{{ $profile->published_projects_count ?? $profile->publishedProjects->count() }} projects</span>
        </div>
        <div class="mt-4 flex flex-wrap gap-1.5">
            @foreach($profile->skills->take(4) as $s)<span class="chip">{{ $s->name }}</span>@endforeach
        </div>
        <div class="mt-auto flex items-center justify-between pt-5">
            <span class="text-sm"><span class="font-semibold">{{ $profile->hourly_rate_minor ? \App\Support\Money::format($profile->hourly_rate_minor, $profile->currency) : '—' }}</span><span class="text-muted"> /hour</span></span>
            @auth
                @if(auth()->user()->is_client && auth()->id() !== $profile->user_id)
                    <form method="POST" action="{{ route('client.shortlist', $profile) }}" class="relative z-10">@csrf
                        <button class="btn btn-sm {{ $shortlisted ? 'btn-dark' : 'btn-light' }}" aria-pressed="{{ $shortlisted ? 'true' : 'false' }}"><x-icon name="bookmark" class="size-4"/>{{ $shortlisted ? 'Saved' : 'Save' }}</button>
                    </form>
                @endif
            @endauth
        </div>
    </div>
</article>
