<x-layouts.app title="Jobs">
@include('admin._nav')
<section class="card overflow-x-auto">
    <table class="w-full min-w-[800px] text-sm">
        <thead class="border-b border-line bg-zinc-50/70"><tr><th class="table-head px-5 py-3">Job</th><th class="table-head px-4 py-3">Client</th><th class="table-head px-4 py-3">Apps</th><th class="table-head px-4 py-3">Status</th><th class="table-head px-4 py-3">Moderate</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($jobs as $j)
                <tr><td class="px-5 py-3"><a href="{{ route('jobs.show', $j) }}" class="font-medium hover:underline">{{ $j->title }}</a><p class="text-xs text-muted">{{ $j->category?->name }} · {{ $j->budgetLabel() }}</p></td><td class="px-4 py-3">{{ $j->client->displayName() }}</td><td class="px-4 py-3">{{ $j->applications_count }}</td><td class="px-4 py-3"><x-status :status="$j->status"/>@if($j->isFeatured()) <span class="pill bg-violet-50 text-violet-700 ring-violet-600/15">Featured</span>@endif</td>
                    <td class="px-4 py-3"><form method="POST" action="{{ route('admin.jobs.moderate', $j) }}" class="flex gap-1">@csrf<select name="action" class="input h-9 w-32 text-xs">@if($j->canTransitionTo('removed'))<option value="remove">Remove</option>@endif<option value="feature">Feature</option><option value="unfeature">Unfeature</option></select><input name="reason" required minlength="5" class="input h-9 w-40 text-xs" placeholder="Reason (logged)"><button class="btn btn-sm btn-light">Apply</button></form></td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $jobs->links() }}</div>
</section>
</x-layouts.app>
