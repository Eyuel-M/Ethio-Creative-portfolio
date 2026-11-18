<x-shell :title="$job->title">
<div class="grid gap-3 sm:gap-4 lg:grid-cols-[1fr_360px]">
    <article class="card card-pad space-y-8 sm:p-8">
        <div>
            <a href="{{ route('jobs.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> All projects</a>
            <div class="mt-4 flex flex-wrap items-center gap-2 text-[13px] text-muted">
                <span>{{ \App\Support\Labels::experience($job->experience_level) }}</span><span>·</span>
                <span>{{ $job->category?->name }}</span><span>·</span>
                <span>Posted {{ $job->published_at?->diffForHumans() ?? 'not yet' }}</span>
                @if($job->status !== 'published')<x-status :status="$job->status"/>@endif
                @if($job->isFeatured())<span class="pill bg-violet-50 text-violet-700 ring-violet-600/15">Featured</span>@endif
            </div>
            <h1 class="mt-2 font-display text-3xl font-bold tracking-tight sm:text-4xl">{{ $job->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2">
                @if($job->client->is_verified)<span class="meta"><x-icon name="verified" class="size-4 text-brand"/> Verified client</span><span class="divider-dot"></span>@endif
                <span class="meta"><x-icon name="pin" class="size-4"/> {{ $job->location ?? 'Ethiopia' }}{{ $job->is_remote ? ' · Remote OK' : '' }}</span><span class="divider-dot"></span>
                <span class="meta"><x-icon name="file" class="size-4"/> {{ $job->applications_count }} proposals</span>
            </div>
        </div>

        <section>
            <h2 class="text-lg font-semibold">About the project</h2>
            <div class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-ink-soft">{{ $job->description }}</div>
        </section>
        @if($job->deliverables)
            <section>
                <h2 class="text-lg font-semibold">Expected deliverables</h2>
                <div class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-ink-soft">{{ $job->deliverables }}</div>
            </section>
        @endif
        @if($job->skills->isNotEmpty())
            <section>
                <h2 class="text-lg font-semibold">Skills &amp; software</h2>
                <div class="mt-3 flex flex-wrap gap-2">@foreach($job->skills as $s)<span class="chip">{{ $s->name }}</span>@endforeach</div>
            </section>
        @endif
        @if($job->questions->isNotEmpty())
            <section class="rounded-2xl bg-zinc-50 p-5">
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="list" class="size-5"/> Screening questions <span class="text-sm font-normal text-muted">({{ $job->questions->count() }})</span></h2>
                <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-ink-soft">
                    @foreach($job->questions as $q)<li>{{ $q->prompt }} @if($q->is_required)<span class="text-rose-500">*</span>@endif</li>@endforeach
                </ol>
            </section>
        @endif
    </article>

    <aside class="space-y-3 sm:space-y-4">
        <div class="card card-pad">
            <p class="text-sm text-muted">{{ $job->budget_type === 'hourly' ? 'Hourly rate' : 'Fixed budget' }}</p>
            <p class="mt-1 font-display text-3xl font-bold">{{ $job->budgetLabel() }}</p>
            <dl class="mt-5 space-y-3 text-sm">
                @foreach([
                    ['clock', 'Duration', $job->duration ?? 'Flexible'],
                    ['calendar', 'Delivery deadline', $job->deadline?->format('M j, Y') ?? 'Flexible'],
                    ['calendar', 'Apply by', $job->application_deadline?->format('M j, Y') ?? 'Until filled'],
                    ['users', 'Hires needed', $job->hires_needed],
                ] as [$icon, $k, $v])
                    <div class="flex items-center justify-between gap-3"><dt class="flex items-center gap-2 text-muted"><x-icon :name="$icon" class="size-4"/>{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>
                @endforeach
            </dl>
            <div class="mt-6 flex gap-2">
                @if($application)
                    <a href="{{ route('applications.show', $application) }}" class="btn btn-dark flex-1">View your application</a>
                @elseif($job->ownedBy(auth()->user()))
                    <a href="{{ route('client.jobs.applicants', $job) }}" class="btn btn-dark flex-1">Review applicants</a>
                @elseif($job->isAcceptingApplications())
                    <a href="{{ route('applications.create', $job) }}" class="btn btn-dark flex-1">Apply Now</a>
                @else
                    <span class="btn btn-muted flex-1">Applications closed</span>
                @endif
                @auth @if(auth()->user()->is_freelancer && ! $job->ownedBy(auth()->user()))
                    <form method="POST" action="{{ route('jobs.save', $job) }}">@csrf<button class="btn btn-light w-11 px-0" aria-label="{{ $saved ? 'Unsave' : 'Save' }}"><x-icon name="bookmark" class="size-5 {{ $saved ? 'fill-ink' : '' }}"/></button></form>
                @endif @endauth
            </div>
        </div>

        <div class="card card-pad">
            <p class="text-sm font-semibold">About the client</p>
            <div class="mt-4 flex items-center gap-3">
                <x-avatar :user="$job->client->user" size="size-12"/>
                <div><p class="font-semibold">{{ $job->client->displayName() }}</p><p class="text-sm text-muted">{{ $job->client->industry ?? 'Client' }}{{ $job->client->location ? ' · '.$job->client->location : '' }}</p></div>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Jobs posted</dt><dd class="mt-0.5 font-semibold">{{ $clientStats['jobs'] }}</dd></div>
                <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Member since</dt><dd class="mt-0.5 font-semibold">{{ $clientStats['since']->format('M Y') }}</dd></div>
            </dl>
        </div>

        @auth
        <details class="card card-pad text-sm">
            <summary class="flex cursor-pointer items-center gap-2 text-muted"><x-icon name="flag" class="size-4"/> Report this job</summary>
            <form method="POST" action="{{ route('reports.store') }}" class="mt-4 space-y-3">@csrf
                <input type="hidden" name="type" value="job"><input type="hidden" name="id" value="{{ $job->id }}">
                <select name="reason" class="input" required><option value="scam">Scam or fraud</option><option value="spam">Spam</option><option value="inappropriate">Inappropriate</option><option value="other">Other</option></select>
                <textarea name="details" class="input" rows="2" placeholder="Details (optional)"></textarea>
                <button class="btn btn-light w-full">Submit report</button>
            </form>
        </details>
        @endauth
    </aside>
</div>

@if($similar->isNotEmpty())
    <section class="card card-pad">
        <h2 class="panel-title">Similar projects</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-3">@foreach($similar as $s)<x-job-card :job="$s" :compact="true"/>@endforeach</div>
    </section>
@endif
</x-shell>
