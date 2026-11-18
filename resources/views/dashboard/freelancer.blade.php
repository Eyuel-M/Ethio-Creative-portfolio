<x-layouts.app title="Dashboard">
<div class="card card-pad bg-gradient-to-br from-white via-white to-[#f3f6ff]">
    @include('dashboard._topbar')
    <div class="mt-6 grid gap-5 xl:grid-cols-[1fr_340px]">
        <div class="min-w-0 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-display text-[28px] font-semibold tracking-tight">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}! <span aria-hidden="true">👋</span></h1>
                <a href="{{ route('portfolio.create') }}" class="btn btn-brand"><x-icon name="plus" class="size-4"/> New Project</a>
            </div>

            @if($stats['completion'] < 100)
                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-dashed border-brand/30 bg-brand-soft/50 px-5 py-4 text-sm">
                    <x-icon name="sparkles" class="size-5 text-brand"/>
                    <p class="flex-1">Your profile is <span class="font-semibold">{{ $stats['completion'] }}% complete</span>. Complete profiles with published work are shown higher to clients.</p>
                    <a href="{{ route('settings') }}" class="btn btn-sm btn-dark">Complete profile</a>
                </div>
            @endif
            @if(optional($profile->paymentDetails)->status !== 'complete')
                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-line bg-white px-5 py-4 text-sm">
                    <x-icon name="bank" class="size-5 text-ink"/>
                    <p class="flex-1">Add your <span class="font-semibold">CBE account or Telebirr number</span> so clients can pay you directly once an agreement is confirmed. It stays private and encrypted.</p>
                    <a href="{{ route('settings', ['tab' => 'payment']) }}" class="btn btn-sm btn-light">Add payment details</a>
                </div>
            @endif

            <div class="grid gap-5 lg:grid-cols-[300px_1fr]">
                <section class="rounded-3xl border border-line bg-white p-5 shadow-[0_10px_30px_-20px_rgba(47,107,255,.35)]">
                    <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Profile strength</h2><a href="{{ route('freelancers.show', $profile) }}" class="text-brand" aria-label="View public profile"><x-icon name="arrow-up-right" class="size-5"/></a></div>
                    <div class="mt-4"><x-ring :value="$stats['completion']" label="Profile complete"/></div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-line p-3"><div class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-lg bg-brand-soft text-brand"><x-icon name="eye" class="size-4"/></span><span class="font-display text-xl font-bold">{{ number_format($stats['profile_views']) }}</span></div><p class="mt-1 text-[11px] text-muted">Profile views</p></div>
                        <div class="rounded-2xl border border-line p-3"><div class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-lg bg-brand-soft text-brand"><x-icon name="star" class="size-4"/></span><span class="font-display text-xl font-bold">{{ $stats['shortlist_rate'] }}%</span></div><p class="mt-1 text-[11px] text-muted">Shortlist rate</p></div>
                    </div>
                </section>
                <section class="min-w-0 rounded-3xl border border-line bg-white p-5 shadow-[0_10px_30px_-20px_rgba(47,107,255,.35)]">
                    <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Applications sent</h2><span class="text-xs text-muted">Last 12 months</span></div>
                    <p class="mt-4 text-sm text-muted">Total applications</p>
                    <p class="flex items-baseline gap-3"><span class="font-display text-3xl font-bold">{{ $stats['applications'] }}</span><span class="flex items-center gap-1 text-xs font-medium text-leaf-dark"><span class="size-2 rounded-full bg-leaf"></span>{{ $stats['response_rate'] }}% viewed or answered</span></p>
                    <div class="mt-3"><x-area-chart :series="$series" label="Applications"/></div>
                </section>
            </div>

            <section>
                <div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Active Projects</h2><a href="{{ route('projects.index') }}" class="text-sm text-muted hover:text-ink">View all</a></div>
                @if($projects->isEmpty())
                    <x-empty class="mt-3" icon="folder" title="No active projects" text="Projects appear here after you and a client both confirm an agreement."><a href="{{ route('jobs.index') }}" class="btn btn-dark">Find projects</a></x-empty>
                @else
                    <div class="mt-3 grid gap-4 md:grid-cols-2">@foreach($projects as $p)<x-project-card :project="$p" :viewer="auth()->user()"/>@endforeach</div>
                @endif
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-3xl border border-line bg-white p-5">
                    <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Recent proposals</h2><a href="{{ route('applications.mine') }}" class="text-sm text-muted hover:text-ink">All</a></div>
                    <ul class="mt-3 divide-y divide-line">
                        @forelse($recentApps as $a)
                            <li><a href="{{ route('applications.show', $a) }}" class="flex items-center justify-between gap-3 py-3"><span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $a->jobPost->title }}</span><span class="text-xs text-muted">{{ $a->created_at->diffForHumans() }}</span></span><x-status :status="$a->status"/></a></li>
                        @empty<li class="py-3 text-sm text-muted">No proposals yet.</li>@endforelse
                    </ul>
                </section>
                <section class="rounded-3xl border border-line bg-white p-5">
                    <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Recommended for you</h2><a href="{{ route('jobs.index', ['category' => $profile->category?->slug]) }}" class="text-sm text-muted hover:text-ink">More</a></div>
                    <ul class="mt-3 divide-y divide-line">
                        @forelse($recommended as $j)
                            <li><a href="{{ route('jobs.show', $j) }}" class="flex items-center justify-between gap-3 py-3"><span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $j->title }}</span><span class="text-xs text-muted">{{ $j->budgetLabel() }} · {{ $j->applications_count }} proposals</span></span><x-icon name="chevron-right" class="size-4 text-muted"/></a></li>
                        @empty<li class="py-3 text-sm text-muted">Set your category in settings to get matches.</li>@endforelse
                    </ul>
                </section>
            </div>
        </div>
        <div class="space-y-5">
            @if($stats['invitations'])
                <a href="{{ route('invitations.index') }}" class="flex items-center gap-3 rounded-2xl bg-ink p-4 text-white"><x-icon name="sparkles" class="size-5 text-leaf"/><span class="flex-1 text-sm">You have <span class="font-semibold">{{ $stats['invitations'] }}</span> pending invitation{{ $stats['invitations'] > 1 ? 's' : '' }}</span><x-icon name="arrow-right" class="size-4"/></a>
            @endif
            <section class="card card-pad">
                <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Portfolio</h2><span class="text-sm text-muted">{{ $stats['published'] }} / {{ $stats['limit'] }} published</span></div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-leaf" style="width: {{ min(100, $stats['published'] / max(1, $stats['limit']) * 100) }}%"></div></div>
                <p class="mt-3 text-sm text-muted">{{ number_format($stats['portfolio_views']) }} total case-study views</p>
            </section>
            @include('dashboard._side')
        </div>
    </div>
</div>
</x-layouts.app>
