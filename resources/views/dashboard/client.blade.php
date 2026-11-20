<x-layouts.app title="Dashboard">
<div class="card card-pad bg-gradient-to-br from-white via-white to-[#f3f6ff]">
    @include('dashboard._topbar')
    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-[1fr_340px]">
        <div class="min-w-0 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-display text-[28px] font-semibold tracking-tight">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}! <span aria-hidden="true">👋</span></h1>
                <a href="{{ route('client.jobs.create') }}" class="btn btn-brand"><x-icon name="plus" class="size-4"/> Post a Job</a>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach([['briefcase', 'Live jobs', $stats['active_jobs']], ['file', 'Applications', $stats['applications']], ['sparkles', 'New to review', $stats['new']], ['folder', 'Active projects', $stats['active_projects']]] as [$icon, $label, $val])
                    <div class="rounded-2xl border border-line bg-white p-4 shadow-[0_10px_30px_-24px_rgba(47,107,255,.5)]"><span class="grid grid-cols-1 size-9 place-items-center rounded-xl bg-brand-soft text-brand"><x-icon :name="$icon" class="size-5"/></span><p class="mt-3 font-display text-2xl font-bold">{{ $val }}</p><p class="text-xs text-muted">{{ $label }}</p></div>
                @endforeach
            </div>
            <section class="min-w-0 rounded-3xl border border-line bg-white p-5 shadow-[0_10px_30px_-20px_rgba(47,107,255,.35)]">
                <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Applications received</h2><span class="text-xs text-muted">Last 12 months</span></div>
                <p class="mt-3 flex items-baseline gap-3"><span class="font-display text-3xl font-bold">{{ $stats['applications'] }}</span><span class="text-xs text-muted">{{ $stats['shortlisted'] }} shortlisted · {{ $stats['saved'] }} saved freelancers</span></p>
                <div class="mt-3"><x-area-chart :series="$series" label="Applications"/></div>
            </section>
            <section>
                <div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Active Projects</h2><a href="{{ route('projects.index') }}" class="text-sm text-muted hover:text-ink">View all</a></div>
                @if($projects->isEmpty())
                    <x-empty class="mt-3" icon="folder" title="No active projects" text="Confirm an agreement with an applicant to start a project workspace."/>
                @else
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">@foreach($projects as $p)<x-project-card :project="$p" :viewer="auth()->user()"/>@endforeach</div>
                @endif
            </section>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <section class="rounded-3xl border border-line bg-white p-5">
                    <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Your jobs</h2><a href="{{ route('client.jobs.index') }}" class="text-sm text-muted hover:text-ink">Manage</a></div>
                    <ul class="mt-3 divide-y divide-line">
                        @forelse($openJobs as $j)
                            <li><a href="{{ in_array($j->status, ['draft', 'pending_payment']) ? route('client.jobs.edit', $j) : route('client.jobs.applicants', $j) }}" class="flex items-center justify-between gap-3 py-3"><span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $j->title }}</span><span class="text-xs text-muted">{{ $j->applications_count }} applicants</span></span><x-status :status="$j->status"/></a></li>
                        @empty<li class="py-3 text-sm text-muted">No jobs yet.</li>@endforelse
                    </ul>
                </section>
                <section class="rounded-3xl border border-line bg-white p-5">
                    <h2 class="text-[17px] font-medium">Latest applicants</h2>
                    <ul class="mt-3 divide-y divide-line">
                        @forelse($recentApps as $a)
                            <li><a href="{{ route('applications.show', $a) }}" class="flex items-center gap-3 py-3"><x-avatar :user="$a->freelancer->user" size="size-9" class="text-xs"/><span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">{{ $a->freelancer->user->name }}</span><span class="block truncate text-xs text-muted">{{ $a->jobPost->title }}</span></span><x-status :status="$a->status"/></a></li>
                        @empty<li class="py-3 text-sm text-muted">No applicants yet.</li>@endforelse
                    </ul>
                </section>
            </div>
        </div>
        <div class="space-y-5">@include('dashboard._side')</div>
    </div>
</div>
</x-layouts.app>
