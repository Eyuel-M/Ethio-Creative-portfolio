<x-layouts.app title="Notifications">
<section class="card card-pad flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="font-display text-3xl font-bold">Notifications</h1><p class="mt-1 text-muted">Updates about applications, agreements, projects and billing.</p></div>
    <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-light">Mark all as read</button></form>
</section>
<section class="card overflow-hidden">
    @forelse($notifications as $n)
        <a href="{{ $n->data['url'] ?? '#' }}" class="flex items-start gap-4 border-b border-line px-5 py-4 last:border-0 hover:bg-zinc-50 sm:px-6 {{ $n->read_at ? '' : 'bg-brand-soft/40' }}">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-soft text-brand"><x-icon :name="$n->data['icon'] ?? 'bell'" class="size-5"/></span>
            <div class="flex-1"><p class="font-medium">{{ $n->data['title'] }}</p><p class="text-sm text-ink-soft">{{ $n->data['body'] }}</p></div>
            <span class="shrink-0 text-xs text-muted">{{ $n->created_at->diffForHumans() }}</span>
        </a>
    @empty
        <x-empty class="m-5" icon="bell" title="You're all caught up"/>
    @endforelse
</section>
{{ $notifications->links() }}
</x-layouts.app>
