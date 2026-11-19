<x-shell :title="$profile->user->displayName()">
@push('meta')
<meta property="og:type" content="profile">
<meta property="og:title" content="{{ $profile->user->displayName() }} — {{ $profile->title ?? 'Creative portfolio' }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($profile->bio ?? 'Portfolio on '.config('marketplace.brand'), 200) }}">
<meta property="og:url" content="{{ route('freelancers.show', $profile) }}">
@if($projects->first()?->coverUrl())<meta property="og:image" content="{{ $projects->first()->coverUrl() }}"><meta name="twitter:card" content="summary_large_image">@endif
@endpush
<section class="card overflow-hidden">
    {{-- Banner: same 5:1 shape as the cover editor's preview, focused where the owner dragged it --}}
    <div class="group relative aspect-[3/1] bg-gradient-to-br from-indigo-200 via-violet-100 to-sky-200 sm:aspect-[5/1]">
        @if($profile->coverUrl())<img src="{{ $profile->coverUrl() }}" alt="" class="size-full object-cover" style="object-position: 50% {{ (int) $profile->cover_position }}%">@elseif($projects->first()?->coverUrl())<img src="{{ $projects->first()->coverUrl() }}" alt="" class="size-full object-cover opacity-90">@endif
        @if($profile->isFeatured())<span class="absolute left-4 top-4 pill bg-white/90 text-violet-700 ring-violet-600/15">Sponsored placement</span>@endif
        @if($isOwner)
            <button type="button" @click="$dispatch('open-cover-editor')" class="absolute bottom-3 right-3 flex items-center gap-2 rounded-full bg-black/60 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-black/75 focus:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
                <x-icon name="camera" class="size-4"/> Change cover
            </button>
        @endif
    </div>
    <div class="px-5 pb-6 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-end gap-5">
                @if($isOwner)
                    <button type="button" @click="$dispatch('open-avatar-editor')" class="group/avatar relative z-10 -mt-14 shrink-0 rounded-full" aria-label="Change profile photo">
                        <x-avatar :user="$profile->user" size="size-28" class="text-3xl ring-[6px] ring-white"/>
                        <span class="absolute inset-[6px] hidden place-items-center rounded-full bg-black/50 text-xs font-semibold text-white opacity-0 transition sm:grid sm:group-hover/avatar:opacity-100 sm:group-focus-visible/avatar:opacity-100">
                            <span class="flex flex-col items-center gap-1"><x-icon name="camera" class="size-5"/>Change photo</span>
                        </span>
                        <span class="absolute bottom-1 right-1 grid size-9 place-items-center rounded-full bg-ink text-white ring-4 ring-white sm:hidden"><x-icon name="camera" class="size-4"/></span>
                    </button>
                @else
                    <x-avatar :user="$profile->user" size="size-28" class="relative z-10 -mt-14 text-3xl ring-[6px] ring-white"/>
                @endif
                <div class="pb-1 pt-4">
                    <h1 class="flex items-center gap-2 font-display text-3xl font-bold">{{ $profile->user->displayName() }} @if($profile->user->hasVerifiedEmail())<x-icon name="verified" class="size-6 text-brand" title="Email verified"/>@endif</h1>
                    <p class="text-ink-soft">{{ $profile->title ?? 'Creative freelancer' }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($profile->is_public)
                    <x-share :url="route('freelancers.show', $profile)" :title="$profile->user->displayName().' — '.($profile->title ?? 'Portfolio')" :image="$projects->first()?->coverUrl()" :subtitle="$projects->count().' published projects'" :label="$isOwner ? 'Share portfolio' : 'Share'"/>
                @endif
                @if($isOwner)
                    <a href="{{ route('settings') }}" class="btn btn-light"><x-icon name="pen" class="size-4"/> Edit profile</a>
                    <a href="{{ route('portfolio.create') }}" class="btn btn-dark"><x-icon name="plus" class="size-4"/> Add project</a>
                @elseif(auth()->user()?->is_client)
                    <form method="POST" action="{{ route('client.shortlist', $profile) }}">@csrf<button class="btn {{ $isShortlisted ? 'btn-dark' : 'btn-light' }}"><x-icon name="bookmark" class="size-4"/>{{ $isShortlisted ? 'Saved' : 'Save' }}</button></form>
                    <div x-data="{ open: false }">
                        <button class="btn btn-dark" @click="open = true"><x-icon name="send" class="size-4"/> Invite to job</button>
                        <div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @keydown.escape.window="open = false">
                            <form method="POST" action="{{ route('client.invite', $profile) }}" class="card w-full max-w-md p-6" @click.outside="open = false">@csrf
                                <h2 class="text-lg font-semibold">Invite {{ $profile->user->displayName() }}</h2>
                                @if($clientJobs->isEmpty())
                                    <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">You need a published job first. <a class="font-semibold underline" href="{{ route('client.jobs.create') }}">Create one</a></p>
                                @else
                                    <label class="label mt-4" for="job_post_id">Job</label>
                                    <select id="job_post_id" name="job_post_id" class="input">@foreach($clientJobs as $j)<option value="{{ $j->id }}">{{ $j->title }}</option>@endforeach</select>
                                    <label class="label mt-3" for="message">Message</label>
                                    <textarea id="message" name="message" rows="3" class="input" placeholder="Hi! I loved your work on…"></textarea>
                                    <label class="label mt-3" for="respond_by">Respond by (optional)</label>
                                    <input id="respond_by" type="date" name="respond_by" class="input">
                                    <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-light" @click="open = false">Cancel</button><button class="btn btn-dark">Send invitation</button></div>
                                @endif
                            </form>
                        </div>
                    </div>
                @elseif(! auth()->check())
                    <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-dark">Sign up to invite</a>
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-soft">
            @if($profile->location)<span class="meta"><x-icon name="pin" class="size-4"/>{{ $profile->location }}</span>@endif
            <span class="meta"><x-icon name="star" class="size-4 {{ $rating['count'] ? 'fill-amber-400 text-amber-400' : '' }}"/>{{ $rating['count'] ? $rating['avg'].' · '.$rating['count'].' reviews' : 'No reviews yet' }}</span>
            <span class="meta"><x-icon name="check-circle" class="size-4"/>{{ $completed }} completed projects</span>
            <span class="pill {{ \App\Support\Labels::tone($profile->availability) }}">{{ ucfirst($profile->availability) }}</span>
            @if($profile->hourly_rate_minor)<span class="meta"><x-icon name="dollar" class="size-4"/>{{ \App\Support\Money::format($profile->hourly_rate_minor, $profile->currency) }}/hr</span>@endif
        </div>
    </div>
</section>

<div class="grid gap-3 sm:gap-4 lg:grid-cols-[1fr_340px]">
    <section class="card card-pad">
        <div class="flex items-center justify-between"><h2 class="panel-title">Portfolio</h2><span class="text-sm text-muted">{{ $projects->count() }} projects</span></div>
        @if($projects->isEmpty())
            <x-empty class="mt-5" icon="image" title="No published projects yet"/>
        @else
            <div class="mt-5 grid gap-4 sm:grid-cols-2">@foreach($projects as $p)<x-portfolio-tile :project="$p->setRelation('profile', $profile)" :showAuthor="false"/>@endforeach</div>
        @endif
    </section>
    <aside class="space-y-3 sm:space-y-4">
        <div class="card card-pad">
            <h2 class="font-semibold">About</h2>
            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-soft">{{ $profile->bio ?? 'This creative hasn\'t written a bio yet.' }}</p>
            @if($profile->skills->isNotEmpty())
                <h3 class="mt-5 text-sm font-semibold">Skills</h3>
                <div class="mt-2 flex flex-wrap gap-1.5">@foreach($profile->skills as $s)<span class="chip">{{ $s->name }}</span>@endforeach</div>
            @endif
            @if($profile->tools)
                <h3 class="mt-5 text-sm font-semibold">Tools</h3>
                <div class="mt-2 flex flex-wrap gap-1.5">@foreach($profile->tools as $t)<span class="chip">{{ $t }}</span>@endforeach</div>
            @endif
            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Experience</dt><dd>{{ \App\Support\Labels::experience($profile->experience_level) }}{{ $profile->years_experience ? ' · '.$profile->years_experience.' yrs' : '' }}</dd></div>
                @if($profile->languages)<div class="flex justify-between"><dt class="text-muted">Languages</dt><dd>{{ implode(', ', $profile->languages) }}</dd></div>@endif
                @if($profile->website)<div class="flex justify-between gap-4"><dt class="text-muted">Website</dt><dd class="truncate"><a href="{{ $profile->website }}" rel="nofollow noopener" target="_blank" class="underline">{{ parse_url($profile->website, PHP_URL_HOST) }}</a></dd></div>@endif
            </dl>
        </div>
        <div class="card card-pad">
            <h2 class="font-semibold">Reviews</h2>
            @forelse($reviews as $r)
                <div class="mt-4 border-t border-line pt-4 first:border-0 first:pt-0">
                    <div class="flex items-center gap-1">@for($i = 1; $i <= 5; $i++)<x-icon name="star" class="size-4 {{ $i <= $r->rating ? 'fill-amber-400 text-amber-400' : 'text-zinc-300' }}"/>@endfor</div>
                    @if($r->body)<p class="mt-2 text-sm text-ink-soft">“{{ $r->body }}”</p>@endif
                    <p class="mt-1 text-xs text-muted">{{ $r->reviewer->displayName() }} · {{ $r->created_at->format('M Y') }}</p>
                </div>
            @empty
                <p class="mt-2 text-sm text-muted">Reviews appear after completed projects.</p>
            @endforelse
        </div>
    </aside>
</div>
@if($isOwner)
    <x-avatar-editor :user="$profile->user"/>
    <x-cover-editor :profile="$profile" :fallback="$projects->first()?->coverUrl()"/>
@endif
</x-shell>
