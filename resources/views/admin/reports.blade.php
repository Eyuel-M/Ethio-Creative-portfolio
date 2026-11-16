<x-layouts.app title="Reports">
@include('admin._nav')
<section class="card overflow-x-auto">
    <table class="w-full min-w-[800px] text-sm">
        <thead class="border-b border-line bg-zinc-50/70"><tr><th class="table-head px-5 py-3">Target</th><th class="table-head px-4 py-3">Reason</th><th class="table-head px-4 py-3">Reporter</th><th class="table-head px-4 py-3">Status</th><th class="table-head px-4 py-3">Resolve</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($reports as $r)
                <tr><td class="px-5 py-3">{{ ucfirst($r->reportable_type) }} <span class="font-mono text-xs text-muted">{{ $r->reportable_id }}</span>@if($r->details)<p class="text-xs text-muted">{{ $r->details }}</p>@endif</td><td class="px-4 py-3">{{ $r->reason }}</td><td class="px-4 py-3">{{ $r->reporter->name }}</td><td class="px-4 py-3"><x-status :status="$r->status"/></td>
                    <td class="px-4 py-3">@if($r->status === 'open')<form method="POST" action="{{ route('admin.reports.resolve', $r) }}" class="flex gap-1">@csrf<select name="status" class="input h-9 w-32 text-xs"><option value="actioned">Actioned</option><option value="dismissed">Dismissed</option></select><input name="reason" required class="input h-9 w-40 text-xs" placeholder="Decision basis"><button class="btn btn-sm btn-light">Save</button></form>@endif</td></tr>
            @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-muted">No reports.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $reports->links() }}</div>
</section>
</x-layouts.app>
