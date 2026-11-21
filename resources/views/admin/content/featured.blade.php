<x-layouts.admin title="Homepage & featured">
<x-admin.header title="Homepage & featured" subtitle="Featured work leads the homepage showcase and the members' feed; featured talent and jobs rise to the top of their lists."/>
<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="Featured portfolio projects" :subtitle="$projects->count().' featured'" :pad="false">
        <ul class="divide-y divide-line">
            @forelse($projects as $p)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover">@endif</span>
                    <span class="min-w-0 flex-1"><a href="{{ route('portfolio.show', $p) }}" target="_blank" class="block truncate text-sm font-medium hover:underline">{{ $p->title }}</a><span class="text-xs text-muted">by {{ $p->profile->user->name }} · since {{ $p->featured_at->format('M j') }}</span></span>
                    <form method="POST" action="{{ route('admin.portfolio.moderate', $p) }}">@csrf<input type="hidden" name="action" value="unfeature"><button class="btn btn-sm btn-light">Unfeature</button></form>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-muted">Nothing featured — the homepage falls back to the most-loved recent work.</li>
            @endforelse
        </ul>
    </x-admin.panel>
    <x-admin.panel title="Suggestions" subtitle="Most appreciated published work that isn't featured yet." :pad="false">
        <ul class="divide-y divide-line">
            @foreach($candidates as $p)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover">@endif</span>
                    <span class="min-w-0 flex-1"><a href="{{ route('portfolio.show', $p) }}" target="_blank" class="block truncate text-sm font-medium hover:underline">{{ $p->title }}</a><span class="text-xs text-muted">{{ $p->likes_count }} appreciations · {{ number_format($p->views) }} views</span></span>
                    <form method="POST" action="{{ route('admin.portfolio.moderate', $p) }}">@csrf<input type="hidden" name="action" value="feature"><button class="btn btn-sm btn-dark"><x-icon name="star" class="size-4"/> Feature</button></form>
                </li>
            @endforeach
        </ul>
    </x-admin.panel>
    <x-admin.panel title="Featured talent" subtitle="Feature a freelancer from their admin profile page." :pad="false">
        <ul class="divide-y divide-line">
            @forelse($talent as $f)
                <li class="flex items-center gap-3 px-5 py-3"><x-avatar :user="$f->user" size="size-9" class="text-xs"/><span class="min-w-0 flex-1"><a href="{{ route('admin.users.show', $f->user) }}" class="block truncate text-sm font-medium hover:underline">{{ $f->user->name }}</a><span class="text-xs text-muted">{{ $f->title }} · until {{ $f->featured_until->format('M j') }}</span></span></li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-muted">No featured freelancers.</li>
            @endforelse
        </ul>
    </x-admin.panel>
    <x-admin.panel title="Featured jobs" subtitle="Paid featured listings and ones featured by moderators." :pad="false">
        <ul class="divide-y divide-line">
            @forelse($jobs as $j)
                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm"><a href="{{ route('jobs.show', $j) }}" target="_blank" class="truncate font-medium hover:underline">{{ $j->title }}</a><span class="shrink-0 text-xs text-muted">{{ $j->client?->displayName() }}{{ $j->featured_until ? ' · until '.$j->featured_until->format('M j') : '' }}</span></li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-muted">No featured jobs.</li>
            @endforelse
        </ul>
    </x-admin.panel>
</div>
</x-layouts.admin>
