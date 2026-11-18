<x-layouts.app title="Audit log">
@include('admin._nav')
<section class="card overflow-x-auto">
    <table class="w-full min-w-[800px] text-sm">
        <thead class="border-b border-line bg-zinc-50/70"><tr><th class="table-head px-5 py-3">When</th><th class="table-head px-4 py-3">Actor</th><th class="table-head px-4 py-3">Action</th><th class="table-head px-4 py-3">Target</th><th class="table-head px-4 py-3">Reason / meta</th></tr></thead>
        <tbody class="divide-y divide-line">@foreach($logs as $l)<tr><td class="px-5 py-3 text-muted">{{ $l->created_at->format('M j, H:i:s') }}</td><td class="px-4 py-3">{{ $l->actor?->name ?? 'System' }}</td><td class="px-4 py-3 tabular-nums tracking-wide text-xs">{{ $l->action }}</td><td class="px-4 py-3 text-xs">{{ $l->target_type }} <span class="tabular-nums tracking-wide text-muted">{{ \Illuminate\Support\Str::limit($l->target_id, 12) }}</span></td><td class="px-4 py-3 text-xs text-muted">{{ $l->reason }} {{ $l->meta ? json_encode($l->meta) : '' }}</td></tr>@endforeach</tbody>
    </table>
    <div class="p-4">{{ $logs->links() }}</div>
</section>
</x-layouts.app>
