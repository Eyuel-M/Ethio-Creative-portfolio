<x-layouts.app title="My Jobs">
<section class="card card-pad">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="font-display text-3xl font-bold">My Jobs</h1><p class="mt-1 text-muted">Drafts, live listings and hires in one place.</p></div>
        <a href="{{ route('client.jobs.create') }}" class="btn btn-dark"><x-icon name="plus" class="size-4"/> Post a job</a>
    </div>
    <div class="mt-5 flex flex-wrap gap-2">
        <a href="{{ route('client.jobs.index') }}" class="btn btn-sm {{ ! $status ? 'btn-dark' : 'btn-light' }}">All <span class="opacity-60">{{ $counts->sum() }}</span></a>
        @foreach(['published', 'draft', 'pending_payment', 'paused', 'filled', 'closed', 'expired'] as $s)
            @if($counts[$s] ?? 0)<a href="{{ route('client.jobs.index', ['status' => $s]) }}" class="btn btn-sm {{ $status === $s ? 'btn-dark' : 'btn-light' }}">{{ \App\Support\Labels::status($s) }} <span class="opacity-60">{{ $counts[$s] }}</span></a>@endif
        @endforeach
    </div>
</section>
@if($jobs->isEmpty())
    <x-empty icon="briefcase" title="No jobs yet" text="Post your first creative job with screening questions to receive comparable applications."><a href="{{ route('client.jobs.create') }}" class="btn btn-dark">Post a job</a></x-empty>
@else
    <div class="space-y-3">
        @foreach($jobs as $job)
            <article class="card flex flex-wrap items-center gap-5 p-5 sm:p-6">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2"><x-status :status="$job->status"/>@if($job->isFeatured())<span class="pill bg-violet-50 text-violet-700 ring-violet-600/15">Featured</span>@endif<span class="text-xs text-muted">{{ $job->category?->name }}</span></div>
                    <h2 class="mt-2 font-display text-lg font-bold"><a href="{{ route('jobs.show', $job) }}" class="hover:underline">{{ $job->title }}</a></h2>
                    <p class="mt-1 text-sm text-muted">{{ $job->budgetLabel() }} · {{ $job->expires_at ? ($job->expires_at->isFuture() ? 'expires '.$job->expires_at->diffForHumans() : 'expired') : 'not published' }}</p>
                </div>
                <dl class="flex gap-6 text-center text-sm">
                    <div><dt class="text-muted">Applicants</dt><dd class="font-display text-xl font-bold">{{ $job->applications_count }}</dd></div>
                    <div><dt class="text-muted">New</dt><dd class="font-display text-xl font-bold text-leaf-dark">{{ $job->new_applications_count }}</dd></div>
                    <div><dt class="text-muted">Shortlisted</dt><dd class="font-display text-xl font-bold">{{ $job->shortlisted_count }}</dd></div>
                </dl>
                <div class="flex flex-wrap gap-2">
                    @if(in_array($job->status, ['draft', 'pending_payment', 'expired']))
                        <a href="{{ route('client.jobs.publish', $job) }}" class="btn btn-dark">Publish</a>
                    @else
                        <a href="{{ route('client.jobs.applicants', $job) }}" class="btn btn-dark">Review applicants</a>
                    @endif
                    @if(! in_array($job->status, ['filled', 'closed', 'removed']))<a href="{{ route('client.jobs.edit', $job) }}" class="btn btn-light" aria-label="Edit"><x-icon name="pen" class="size-4"/></a>@endif
                    @if(in_array($job->status, ['published', 'paused']))
                        <div x-data="{ open: false }" class="relative">
                            <button class="btn btn-light w-11 px-0" @click="open = !open" aria-label="More actions"><x-icon name="more" class="size-5"/></button>
                            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-2 w-48 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                                @foreach(($job->status === 'published' ? ['paused' => 'Pause listing', 'filled' => 'Mark as filled', 'closed' => 'Close job'] : ['published' => 'Resume listing', 'filled' => 'Mark as filled', 'closed' => 'Close job']) as $to => $label)
                                    <form method="POST" action="{{ route('client.jobs.status', $job) }}">@csrf<input type="hidden" name="status" value="{{ $to }}"><button class="w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-zinc-50">{{ $label }}</button></form>
                                @endforeach
                                @if($job->status === 'published' && ! $job->isFeatured())
                                    <form method="POST" action="{{ route('client.jobs.checkout', $job) }}">@csrf<input type="hidden" name="product" value="job_feature_boost"><button class="w-full rounded-xl px-3 py-2 text-left text-sm text-violet-700 hover:bg-violet-50">Feature this job</button></form>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="card card-pad">{{ $jobs->links() }}</div>
@endif
</x-layouts.app>
