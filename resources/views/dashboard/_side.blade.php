<section class="card card-pad">
    <div class="flex items-center justify-between"><h2 class="text-[17px] font-medium">Recent Activity</h2><a href="{{ route('notifications.index') }}" class="text-sm text-ink-soft hover:text-ink">View All</a></div>
    <ol class="mt-5 space-y-5">
        @forelse($activity as $n)
            <li class="relative flex gap-4 [&:not(:last-child)]:after:absolute [&:not(:last-child)]:after:left-[19px] [&:not(:last-child)]:after:top-11 [&:not(:last-child)]:after:h-[calc(100%-28px)] [&:not(:last-child)]:after:w-px [&:not(:last-child)]:after:bg-line">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-soft text-brand"><x-icon :name="$n->data['icon'] ?? 'bell'" class="size-5"/></span>
                <a href="{{ $n->data['url'] ?? '#' }}" class="min-w-0"><p class="font-medium leading-snug">{{ $n->data['title'] }}</p><p class="text-sm text-ink-soft">{{ \Illuminate\Support\Str::limit($n->data['body'], 70) }}</p><p class="mt-0.5 text-xs text-muted">{{ $n->created_at->diffForHumans() }}</p></a>
            </li>
        @empty
            <li class="text-sm text-muted">Nothing yet — activity on your jobs and projects shows up here.</li>
        @endforelse
    </ol>
</section>
<section class="card card-pad">
    <div class="flex items-center justify-between"><h2 class="flex items-center gap-2 text-[17px] font-medium"><x-icon name="message" class="size-5 text-brand"/> Messages @if($navCounts['messages'] ?? 0)<span class="grid size-5 place-items-center rounded-full bg-leaf text-[11px] text-white">{{ $navCounts['messages'] }}</span>@endif</h2><a href="{{ route('messages.index') }}" class="text-sm text-ink-soft hover:text-ink">View All</a></div>
    <div class="mt-4 space-y-2">
        @forelse($conversations as $c)
            @php $other = $c->otherParty(auth()->user()); $unread = $c->unreadFor(auth()->user()); @endphp
            <a href="{{ route('messages.show', $c) }}" class="flex items-center gap-3 rounded-xl border p-3 {{ $unread ? 'border-brand/20 bg-brand-soft/60' : 'border-transparent hover:bg-zinc-50' }}">
                <x-avatar :user="$other" size="size-11"/>
                <div class="min-w-0 flex-1"><div class="flex justify-between gap-2"><p class="truncate font-semibold">{{ $other->name }}</p><span class="shrink-0 text-xs text-muted">{{ $c->latestMessage?->created_at?->diffForHumans(short: true) }}</span></div><p class="truncate text-sm text-ink-soft">{{ $c->latestMessage?->body ?? 'Conversation started' }}</p></div>
                @if($unread)<span class="size-2 rounded-full bg-brand"></span>@endif
            </a>
        @empty
            <p class="text-sm text-muted">No conversations yet.</p>
        @endforelse
    </div>
</section>
