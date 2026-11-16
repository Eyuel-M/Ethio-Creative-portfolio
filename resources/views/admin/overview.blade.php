<x-layouts.app title="Admin">
@include('admin._nav')
<div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    @foreach([['Users', $stats['users'], 'users'], ['Freelancers', $stats['freelancers'], 'pen'], ['Clients', $stats['clients'], 'briefcase'], ['New this week', $stats['new_week'], 'sparkles'], ['Live jobs', $stats['active_jobs'], 'megaphone'], ['Applications', $stats['applications'], 'file'], ['Active projects', $stats['projects_active'], 'folder'], ['Completed projects', $stats['projects_completed'], 'check-circle'], ['Open reports', $stats['open_reports'], 'flag'], ['Platform revenue', \App\Support\Money::format($stats['revenue'], 'ETB'), 'dollar'], ['Orders pending', $stats['pending_orders'], 'clock']] as [$l, $v, $i])
        <div class="card p-4"><span class="grid size-9 place-items-center rounded-xl bg-zinc-100"><x-icon :name="$i" class="size-5"/></span><p class="mt-3 font-display text-2xl font-bold">{{ $v }}</p><p class="text-xs text-muted">{{ $l }}</p></div>
    @endforeach
</div>
<section class="card card-pad">
    <div class="flex justify-between"><h2 class="panel-title">Recent audited actions</h2><a href="{{ route('admin.audit') }}" class="text-sm text-muted">All</a></div>
    <ul class="mt-4 divide-y divide-line text-sm">@forelse($recent as $l)<li class="flex justify-between gap-3 py-2.5"><span><span class="font-mono text-xs">{{ $l->action }}</span> · {{ $l->actor?->name ?? 'System' }} {{ $l->target_type ? '→ '.$l->target_type : '' }}</span><span class="text-muted">{{ $l->created_at->diffForHumans() }}</span></li>@empty<li class="py-3 text-muted">No entries.</li>@endforelse</ul>
</section>
</x-layouts.app>
