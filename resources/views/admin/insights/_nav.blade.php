<div class="flex flex-wrap items-center justify-between gap-3">
    <nav class="flex flex-wrap gap-1 rounded-full border border-line bg-white p-1 text-[13px] font-medium">
        @foreach([['Traffic & sources', 'admin.insights.traffic'], ['Content & behaviour', 'admin.insights.content'], ['Funnels', 'admin.insights.funnels'], ['Marketplace health', 'admin.insights.marketplace']] as [$l, $r])
            <a href="{{ route($r, ['days' => $days]) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs($r) ? 'bg-ink text-white' : 'text-muted hover:text-ink' }}">{{ $l }}</a>
        @endforeach
    </nav>
    <x-admin.range :days="$days"/>
</div>
