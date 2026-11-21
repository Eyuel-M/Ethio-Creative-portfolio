@php use App\Support\Money; @endphp
<x-layouts.admin title="Payment issues">
<x-admin.header title="Payment issues" subtitle="Failed or stuck payments from the last 14 days, and provider notifications that haven't been processed. Reach out to members who got stuck."/>
<x-admin.panel title="Failed or pending orders" :pad="false">
    <ul class="divide-y divide-line">
        @forelse($orders as $o)
            <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                <span class="min-w-0 flex-1"><a href="{{ route('admin.users.show', $o->user) }}" class="font-medium hover:underline">{{ $o->user->name }}</a> <span class="text-muted">· {{ $o->user->email }}</span><span class="block text-xs text-muted">{{ $o->product?->name }} · {{ Money::format($o->amount_minor, $o->currency) }} · {{ $o->created_at->diffForHumans() }} · {{ $o->provider }}</span></span>
                <x-status :status="$o->status"/>
            </li>
        @empty
            <li class="px-5 py-10 text-center text-sm text-muted">No payment problems. 🎉</li>
        @endforelse
    </ul>
</x-admin.panel>
<x-admin.panel title="Unprocessed provider notifications" subtitle="Payment confirmations (webhooks) received but not applied — usually a mismatch or an unknown order." :pad="false">
    <ul class="divide-y divide-line">
        @forelse($events as $e)
            <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm"><span class="tabular-nums text-xs">{{ $e->provider }} · {{ $e->type }} · {{ $e->provider_event_id }}</span><span class="text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($e->created_at)->diffForHumans() }}</span></li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-muted">All provider notifications were processed.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $orders->links() }}
</x-layouts.admin>
