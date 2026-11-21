<x-layouts.admin title="Reviews">
<x-admin.header title="Reviews" subtitle="Ratings left after projects. Hide abusive or fake reviews — hidden ones stop counting toward ratings."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['visible' => ['Visible', null], 'hidden' => ['Hidden', null]]"/>
    <ul class="divide-y divide-line">
        @forelse($reviews as $r)
            <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm"><span class="text-amber-500">{{ str_repeat('★', $r->rating) }}</span><span class="text-zinc-300">{{ str_repeat('★', 5 - $r->rating) }}</span> <span class="font-medium">{{ $r->reviewer?->name }}</span> <span class="text-muted">reviewed</span> <span class="font-medium">{{ $reviewees[$r->reviewee_id]?->name ?? '—' }}</span></p>
                    @if($r->body)<p class="mt-1 text-sm text-ink-soft">“{{ $r->body }}”</p>@endif
                    <p class="mt-1 text-xs text-muted">{{ $r->project?->title }} · {{ $r->created_at->format('M j, Y') }}@if($r->hidden_reason) · hidden: {{ $r->hidden_reason }}@endif</p>
                </div>
                <x-admin.action :action="route('admin.reviews.toggle', $r)" :label="$r->hidden_at ? 'Restore' : 'Hide'" :tone="$r->hidden_at ? 'light' : 'danger'"/>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">No reviews here.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $reviews->links() }}
</x-layouts.admin>
