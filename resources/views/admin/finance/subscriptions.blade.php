<x-layouts.admin title="Subscriptions">
<x-admin.header title="Subscriptions & active purchases" subtitle="Professional plans, featured listings and paid job posts, with when they end."/>
<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    @forelse($byProduct as $name => $n)<x-admin.stat :label="$name" :value="number_format($n)" hint="active now"/>@empty<x-admin.stat label="Active" value="0"/>@endforelse
</div>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['active' => ['Active', null], 'expiring' => ['Ending in 7 days', null], 'expired' => ['Ended', null]]"/>
    <ul class="divide-y divide-line">
        @forelse($items as $e)
            <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                <span class="min-w-0 flex-1"><a href="{{ route('admin.users.show', $e->user) }}" class="font-medium hover:underline">{{ $e->user->name }}</a><span class="block text-xs text-muted">{{ $e->product?->name }} · started {{ $e->starts_at->format('M j, Y') }}</span></span>
                <span class="text-xs {{ $e->ends_at->isPast() ? 'text-muted' : ($e->ends_at->lt(now()->addDays(7)) ? 'font-semibold text-amber-700' : 'text-ink-soft') }}">{{ $e->ends_at->isPast() ? 'Ended' : 'Ends' }} {{ $e->ends_at->format('M j, Y') }}</span>
            </li>
        @empty
            <li class="px-5 py-10 text-center text-sm text-muted">Nothing here.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $items->links() }}
</x-layouts.admin>
