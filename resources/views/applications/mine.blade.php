<x-layouts.app title="My Proposals">
<section class="card card-pad">
    <h1 class="font-display text-3xl font-bold">My Proposals</h1>
    <p class="mt-1 text-muted">Track every application from submission to agreement.</p>
    <div class="mt-5 flex flex-wrap gap-2">
        <a href="{{ route('applications.mine') }}" class="btn btn-sm {{ ! $status ? 'btn-dark' : 'btn-light' }}">All <span class="opacity-60">{{ $counts->sum() }}</span></a>
        @foreach(\App\Models\Application::STATUSES as $s)
            @if($counts[$s] ?? 0)<a href="{{ route('applications.mine', ['status' => $s]) }}" class="btn btn-sm {{ $status === $s ? 'btn-dark' : 'btn-light' }}">{{ \App\Support\Labels::status($s) }} <span class="opacity-60">{{ $counts[$s] }}</span></a>@endif
        @endforeach
    </div>
</section>
@if($applications->isEmpty())
    <x-empty icon="file" title="No applications yet" text="Find a project that fits your skills and send a proposal."><a href="{{ route('jobs.index') }}" class="btn btn-dark">Find projects</a></x-empty>
@else
    <section class="card overflow-hidden">
        <ul class="divide-y divide-line">
            @foreach($applications as $a)
                <li><a href="{{ route('applications.show', $a) }}" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-zinc-50 sm:px-6">
                    <x-avatar :user="$a->jobPost->client->user" size="size-11"/>
                    <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $a->jobPost->title }}</p><p class="text-sm text-muted">{{ $a->jobPost->client->displayName() }} · applied {{ $a->created_at->diffForHumans() }}</p></div>
                    <div class="text-right text-sm"><p class="font-semibold">{{ \App\Support\Money::format($a->proposed_amount_minor, $a->currency) }}</p><p class="text-muted">{{ $a->estimated_days }} days</p></div>
                    <x-status :status="$a->status"/>
                </a></li>
            @endforeach
        </ul>
    </section>
    @if($applications->hasPages())<div class="card card-pad">{{ $applications->links() }}</div>@endif
@endif
</x-layouts.app>
