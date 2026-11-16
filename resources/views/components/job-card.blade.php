@props(['job', 'saved' => false, 'applied' => false, 'compact' => false])
@php
    $client = $job->client;
    $acceptingApps = $job->isAcceptingApplications();
@endphp
<article class="card group relative flex flex-col p-5 transition hover:shadow-[0_10px_30px_-18px_rgba(12,12,16,.35)] sm:p-6">
    <div class="flex items-center justify-between text-[13px] text-muted">
        <span class="flex items-center gap-2">
            {{ \App\Support\Labels::experience($job->experience_level) }}
            @if($job->isFeatured())<span class="pill bg-violet-50 text-violet-700 ring-violet-600/15"><x-icon name="sparkles" class="size-3"/> Featured</span>@endif
        </span>
        <span class="flex items-center gap-2">
            <span>{{ $job->published_at?->diffForHumans(short: false) ?? 'Draft' }}</span>
            @auth
                @if(auth()->user()->is_freelancer)
                    <form method="POST" action="{{ route('jobs.save', $job) }}">@csrf
                        <button class="rounded-lg p-1 transition hover:bg-zinc-100 {{ $saved ? 'text-ink' : 'text-faint' }}" aria-label="{{ $saved ? 'Unsave job' : 'Save job' }}" title="{{ $saved ? 'Saved' : 'Save' }}">
                            <x-icon name="bookmark" class="size-[18px] {{ $saved ? 'fill-ink' : '' }}"/>
                        </button>
                    </form>
                @endif
            @endauth
        </span>
    </div>

    <h3 class="mt-2 font-display text-lg font-bold leading-snug sm:text-[20px]">
        <a href="{{ route('jobs.show', $job) }}" class="after:absolute after:inset-0 after:content-[''] focus:outline-none">{{ $job->title }}</a>
    </h3>

    <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
        @if($client->is_verified)
            <span class="meta"><x-icon name="verified" class="size-4 text-brand"/> Verified</span><span class="divider-dot"></span>
        @endif
        <span class="meta"><x-icon name="pin" class="size-4"/> {{ $job->is_remote ? ($job->location ? $job->location.' · Remote' : 'Remote') : ($job->location ?? 'Ethiopia') }}</span>
        <span class="divider-dot"></span>
        @if($acceptingApps)
            <span class="pill bg-leaf-soft text-leaf-dark ring-leaf/20"><x-icon name="refresh" class="size-3.5"/> Still Selecting</span>
        @else
            <span class="pill bg-amber-50 text-amber-700 ring-amber-600/20"><x-icon name="user" class="size-3.5"/> Not accepting</span>
        @endif
    </div>

    @unless($compact)
        <p class="mt-4 line-clamp-2 text-[15px] leading-relaxed text-muted">{{ $job->description }}</p>
        @if($job->relationLoaded('skills') && $job->skills->isNotEmpty())
            <div class="relative z-10 mt-4 flex flex-wrap gap-2">
                @foreach($job->skills->take(6) as $skill)
                    <a href="{{ route('jobs.index', ['q' => $skill->name]) }}" class="chip hover:border-zinc-300">{{ $skill->name }}</a>
                @endforeach
            </div>
        @endif
    @endunless

    <div class="mt-auto flex items-center justify-between gap-3 pt-5">
        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 text-[15px]">
            <span class="flex items-center gap-1.5"><x-icon name="dollar" class="size-5 text-violet"/> <span class="font-medium">{{ $job->budgetLabel() }}</span></span>
            <span class="divider-dot hidden sm:block"></span>
            <span class="flex items-center gap-1.5 text-muted"><x-icon name="file" class="size-5 text-violet"/> <span class="font-medium text-ink">{{ $job->applications_count ?? $job->applications()->count() }}</span> proposals</span>
        </div>
        <div class="relative z-10 shrink-0">
            @if($applied)
                <span class="btn btn-muted">Applied</span>
            @elseif($acceptingApps)
                @auth
                    @if(auth()->user()->is_freelancer && ! $job->ownedBy(auth()->user()))
                        <a href="{{ route('applications.create', $job) }}" class="btn btn-dark">Apply Now</a>
                    @else
                        <a href="{{ route('jobs.show', $job) }}" class="btn btn-light">View</a>
                    @endif
                @else
                    <a href="{{ route('applications.create', $job) }}" class="btn btn-dark">Apply Now</a>
                @endauth
            @endif
        </div>
    </div>
</article>
