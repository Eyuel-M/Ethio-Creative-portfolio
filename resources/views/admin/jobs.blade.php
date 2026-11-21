@php use App\Support\Money; @endphp
<x-layouts.admin title="Job moderation">
<x-admin.header title="Job moderation" subtitle="New jobs land here for a quick check. Approve, feature or remove — every decision is logged and removals notify the client."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['new' => ['New to check', $newCount], 'published' => ['Live', $counts['published'] ?? 0], 'featured' => ['Featured', null], 'pending_payment' => ['Awaiting payment', $counts['pending_payment'] ?? 0], 'removed' => ['Removed', $counts['removed'] ?? 0], 'all' => ['All', $counts->sum()]]"/>
    <form class="border-b border-line p-3 sm:px-5"><input type="hidden" name="status" value="{{ $tab }}"><div class="relative max-w-sm"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/><input name="q" value="{{ request('q') }}" class="input h-10 pl-9" placeholder="Title or ID"></div></form>
    <ul class="divide-y divide-line">
        @forelse($jobs as $j)
            <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2"><a href="{{ route('jobs.show', $j) }}" target="_blank" class="font-medium hover:underline">{{ $j->title }}</a><x-status :status="$j->status"/>@if($j->isFeatured())<span class="pill bg-violet-50 text-violet-700 ring-violet-600/15">Featured</span>@endif</div>
                    <p class="mt-0.5 text-xs text-muted">{{ $j->client?->displayName() }}{{ $j->client?->is_verified ? ' ✓' : '' }} · {{ $j->category?->name }} · {{ $j->budgetLabel() }} · {{ $j->applications_count }} applicants · {{ $j->published_at?->diffForHumans() ?? 'not published' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($tab === 'new')<x-admin.action :action="route('admin.jobs.moderate', $j)" :fields="['action' => 'approve']" label="Approve" tone="dark" placeholder="Optional note (logged)" />@endif
                    <x-admin.action :action="route('admin.jobs.moderate', $j)" :fields="['action' => $j->isFeatured() ? 'unfeature' : 'feature']" :label="$j->isFeatured() ? 'Unfeature' : 'Feature 14d'"/>
                    @if($j->status === 'published')<x-admin.action :action="route('admin.jobs.moderate', $j)" :fields="['action' => 'remove']" label="Remove" tone="danger" confirm="The client is notified with your reason."/>@endif
                </div>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">No jobs here.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $jobs->links() }}
</x-layouts.admin>
