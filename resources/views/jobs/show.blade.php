@php
    $typeIcon = ['multiple_choice' => 'list', 'multi_select' => 'check-circle', 'yes_no' => 'check', 'number' => 'chart', 'text' => 'pen', 'portfolio_link' => 'link', 'file' => 'upload'];
    $deliverables = collect(preg_split('/\r?\n/', (string) $job->deliverables))->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
    $closesIn = $job->application_deadline && $job->application_deadline->endOfDay()->isFuture() ? (int) now()->startOfDay()->diffInDays($job->application_deadline->startOfDay()) : null;
    $required = $job->questions->where('is_required', true)->count();
    $facts = [
        ['clock', 'Duration', $job->duration ?? 'Flexible'],
        ['calendar', 'Delivery by', $job->deadline?->format('M j, Y') ?? 'Flexible'],
        ['send', 'Apply by', $job->application_deadline?->format('M j, Y') ?? 'Until filled'],
        ['users', 'Hires', $job->hires_needed.' '.\Illuminate\Support\Str::plural('freelancer', $job->hires_needed)],
    ];
    $isOwner = $job->ownedBy(auth()->user());
    $canSave = auth()->check() && auth()->user()->is_freelancer && ! $isOwner;
@endphp
<x-shell :title="$job->title">

{{-- ───────── Hero ───────── --}}
<section class="card overflow-hidden">
    <div class="relative bg-gradient-to-br from-zinc-50 via-white to-violet-50/60 px-5 pb-8 pt-5 sm:px-8 sm:pb-10 sm:pt-7 lg:px-10">
        <a href="{{ route('jobs.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> All jobs</a>

        <div class="mt-6 flex flex-wrap items-center gap-2">
            @if($job->category)<span class="inline-flex items-center rounded-full bg-ink px-3 py-1 text-xs font-medium text-white">{{ $job->category->name }}</span>@endif
            <span class="inline-flex items-center rounded-full border border-line bg-white px-3 py-1 text-xs font-medium text-ink-soft">{{ \App\Support\Labels::experience($job->experience_level) }} level</span>
            @if($job->isFeatured())<span class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-3 py-1 text-xs font-medium text-violet-700"><x-icon name="sparkles" class="size-3.5"/> Featured</span>@endif
            @if($job->status !== 'published')<x-status :status="$job->status"/>@endif
        </div>

        <h1 class="mt-4 max-w-4xl font-display text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">{{ $job->title }}</h1>

        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="flex items-center gap-3">
                <x-avatar :user="$job->client->user" size="size-10" class="text-xs"/>
                <div class="leading-tight">
                    <p class="flex items-center gap-1 text-sm font-semibold">{{ $job->client->displayName() }} @if($job->client->is_verified)<x-icon name="verified" class="size-4 text-brand" title="Verified client"/>@endif</p>
                    <p class="text-xs text-muted">Posted {{ $job->published_at?->diffForHumans() ?? 'not yet' }}</p>
                </div>
            </div>
            <span class="hidden h-8 w-px bg-line sm:block"></span>
            <span class="meta"><x-icon name="pin" class="size-4"/> {{ $job->location ?? 'Ethiopia' }}</span>
            @if($job->is_remote)<span class="meta"><x-icon name="monitor" class="size-4"/> Remote OK</span>@endif
            <span class="meta"><x-icon name="file" class="size-4"/> {{ $job->applications_count }} {{ \Illuminate\Support\Str::plural('proposal', $job->applications_count) }}</span>
        </div>
    </div>

    {{-- Key facts strip --}}
    <dl class="grid grid-cols-2 gap-px border-t border-line bg-line sm:grid-cols-4 lg:grid-cols-5">
        <div class="col-span-2 bg-white px-5 py-5 sm:col-span-4 sm:px-8 lg:col-span-1 lg:px-10">
            <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ $job->budget_type === 'hourly' ? 'Hourly rate' : 'Fixed budget' }}</dt>
            <dd class="mt-1 font-display text-2xl font-bold">{{ $job->budgetLabel() }}</dd>
        </div>
        @foreach($facts as [$icon, $k, $v])
            <div class="bg-white px-5 py-5 sm:px-6">
                <dt class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider text-muted"><x-icon :name="$icon" class="size-3.5"/> {{ $k }}</dt>
                <dd class="mt-1.5 text-[15px] font-semibold">{{ $v }}</dd>
            </div>
        @endforeach
    </dl>
</section>

{{-- ───────── Body ───────── --}}
<div class="grid grid-cols-1 items-start gap-3 sm:gap-4 lg:grid-cols-[minmax(0,1fr)_380px]">
    <div class="space-y-3 sm:space-y-4">
        <section class="card card-pad sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">The brief</p>
            <div class="mt-4 max-w-3xl whitespace-pre-line text-[16px] leading-[1.75] text-ink-soft">{{ $job->description }}</div>

            @if($job->skills->isNotEmpty())
                <div class="mt-8 border-t border-line pt-6">
                    <p class="text-sm font-semibold">Skills &amp; software</p>
                    <div class="mt-3 flex flex-wrap gap-2">@foreach($job->skills as $s)<span class="rounded-full bg-zinc-100 px-3 py-1.5 text-[13px] font-medium text-ink-soft">{{ $s->name }}</span>@endforeach</div>
                </div>
            @endif
        </section>

        @if($deliverables->isNotEmpty())
            <section class="card card-pad sm:p-8">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="font-display text-xl font-bold tracking-tight">What you'll deliver</h2>
                    <span class="text-sm text-muted">{{ $deliverables->count() }} {{ \Illuminate\Support\Str::plural('item', $deliverables->count()) }}</span>
                </div>
                <ul class="mt-5 grid gap-2.5 sm:grid-cols-2">
                    @foreach($deliverables as $d)
                        <li class="flex items-start gap-3 rounded-xl border border-line p-3.5 text-[15px]">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-leaf/15 text-leaf-dark"><x-icon name="check" class="size-3.5"/></span>
                            <span class="pt-0.5">{{ $d }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($job->questions->isNotEmpty())
            <section class="card card-pad sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-bold tracking-tight">Screening questions</h2>
                        <p class="mt-1 text-sm text-muted">You'll answer these when you apply. {{ $required }} of {{ $job->questions->count() }} required.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-ink-soft"><x-icon name="clock" class="size-3.5"/> ~{{ max(2, $job->questions->count() * 2) }} min</span>
                </div>
                <ol class="mt-5 divide-y divide-line overflow-hidden rounded-xl border border-line">
                    @foreach($job->questions as $q)
                        <li class="flex items-start gap-4 bg-white p-4">
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-zinc-100 font-display text-sm font-bold text-ink-soft">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[15px] font-medium leading-snug">{{ $q->prompt }}</p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-muted">
                                    <span class="inline-flex items-center gap-1"><x-icon :name="$typeIcon[$q->type] ?? 'pen'" class="size-3.5"/> {{ \App\Models\ScreeningQuestion::TYPES[$q->type] ?? 'Answer' }}</span>
                                    @if($q->hasOptions() && $q->options)<span class="text-faint">·</span><span>{{ count($q->options) }} options</span>@endif
                                </p>
                            </div>
                            @if($q->is_required)
                                <span class="shrink-0 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-semibold text-rose-600">Required</span>
                            @else
                                <span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-0.5 text-[11px] font-medium text-muted">Optional</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </div>

    {{-- ───────── Sidebar ───────── --}}
    <aside class="space-y-3 sm:space-y-4 lg:sticky lg:top-20">
        <div id="apply" class="card card-pad">
            @if($job->isAcceptingApplications())
                <p class="flex items-center gap-2 text-sm font-medium text-leaf-dark"><span class="size-2 rounded-full bg-leaf"></span> Accepting proposals</p>
            @else
                <p class="flex items-center gap-2 text-sm font-medium text-muted"><span class="size-2 rounded-full bg-zinc-300"></span> Not accepting proposals</p>
            @endif
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-xl bg-zinc-50 p-3"><p class="text-xs text-muted">Proposals</p><p class="mt-0.5 font-semibold">{{ $job->applications_count }}</p></div>
                <div class="rounded-xl bg-zinc-50 p-3"><p class="text-xs text-muted">Closes</p><p class="mt-0.5 font-semibold">{{ $closesIn === null ? ($job->application_deadline ? 'Closed' : 'When filled') : ($closesIn === 0 ? 'Today' : 'In '.$closesIn.' '.\Illuminate\Support\Str::plural('day', $closesIn)) }}</p></div>
            </div>

            <div class="mt-5 flex gap-2">
                @if($application)
                    <a href="{{ route('applications.show', $application) }}" class="btn btn-dark h-12 flex-1"><x-icon name="check-circle" class="size-4"/> View your application</a>
                @elseif($isOwner)
                    <a href="{{ route('client.jobs.applicants', $job) }}" class="btn btn-dark h-12 flex-1">Review applicants</a>
                @elseif($job->isAcceptingApplications())
                    <a href="{{ route('applications.create', $job) }}" class="btn btn-dark h-12 flex-1">Apply Now <x-icon name="arrow-right" class="size-4"/></a>
                @else
                    <span class="btn btn-muted h-12 flex-1">Applications closed</span>
                @endif
                @if($canSave)
                    <form method="POST" action="{{ route('jobs.save', $job) }}">@csrf<button class="btn btn-light size-12 px-0" aria-label="{{ $saved ? 'Unsave' : 'Save' }}" title="{{ $saved ? 'Saved' : 'Save for later' }}"><x-icon name="bookmark" class="size-5 {{ $saved ? 'fill-ink' : '' }}"/></button></form>
                @endif
                @if($job->isLive())
                    <x-share :url="route('jobs.show', $job)" :title="$job->title" :subtitle="$job->client->displayName().' · '.$job->budgetLabel()" :square="true"/>
                @endif
            </div>

            @if(! $application && ! $isOwner && $job->isAcceptingApplications())
                <ul class="mt-5 space-y-2 border-t border-line pt-5 text-sm text-ink-soft">
                    <li class="flex items-center gap-2"><x-icon name="pen" class="size-4 text-muted"/> A short cover message</li>
                    <li class="flex items-center gap-2"><x-icon name="dollar" class="size-4 text-muted"/> Your price and delivery time</li>
                    @if($job->questions->isNotEmpty())<li class="flex items-center gap-2"><x-icon name="list" class="size-4 text-muted"/> {{ $job->questions->count() }} screening {{ \Illuminate\Support\Str::plural('question', $job->questions->count()) }}</li>@endif
                    <li class="flex items-center gap-2"><x-icon name="layers" class="size-4 text-muted"/> Up to 6 portfolio projects</li>
                </ul>
            @endif
        </div>

        <div class="card card-pad">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">About the client</p>
            <div class="mt-4 flex items-center gap-3">
                <x-avatar :user="$job->client->user" size="size-12"/>
                <div class="min-w-0">
                    <p class="flex items-center gap-1 truncate font-semibold">{{ $job->client->displayName() }} @if($job->client->is_verified)<x-icon name="verified" class="size-4 shrink-0 text-brand"/>@endif</p>
                    <p class="truncate text-sm text-muted">{{ $job->client->industry ?? 'Client' }}{{ $job->client->location ? ' · '.$job->client->location : '' }}</p>
                </div>
            </div>
            <dl class="mt-5 divide-y divide-line text-sm">
                <div class="flex justify-between py-2.5"><dt class="text-muted">Jobs posted</dt><dd class="font-semibold">{{ $clientStats['jobs'] }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-muted">Member since</dt><dd class="font-semibold">{{ $clientStats['since']->format('M Y') }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-muted">Identity</dt><dd class="font-semibold">{{ $job->client->is_verified ? 'Verified' : 'Not verified yet' }}</dd></div>
            </dl>
        </div>

        @auth
        <details class="group px-1 text-sm">
            <summary class="flex cursor-pointer list-none items-center gap-2 text-muted hover:text-ink"><x-icon name="flag" class="size-4"/> Report this job</summary>
            <form method="POST" action="{{ route('reports.store') }}" class="card card-pad mt-3 space-y-3">@csrf
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
    <section class="pt-4">
        <div class="flex items-end justify-between gap-3 px-1">
            <h2 class="font-display text-2xl font-bold tracking-tight">Similar jobs</h2>
            <a href="{{ route('jobs.index', ['category' => $job->category?->slug]) }}" class="text-sm font-medium text-muted hover:text-ink">See all <x-icon name="arrow-right" class="inline size-4"/></a>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">@foreach($similar as $s)<x-job-card :job="$s" :compact="true"/>@endforeach</div>
    </section>
@endif

{{-- Phones: keep the main action in reach --}}
@if(! $application && ! $isOwner && $job->isAcceptingApplications())
    <div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-line bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="min-w-0 flex-1"><p class="truncate font-display text-lg font-bold leading-tight">{{ $job->budgetLabel() }}</p><p class="text-xs text-muted">{{ $job->applications_count }} {{ \Illuminate\Support\Str::plural('proposal', $job->applications_count) }}</p></div>
        <a href="{{ route('applications.create', $job) }}" class="btn btn-dark h-11 px-6">Apply Now</a>
    </div>
    <div class="h-16 lg:hidden"></div>
@endif
</x-shell>
