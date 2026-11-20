<x-layouts.app title="Messages">
<div class="card grid h-[calc(100vh-7rem)] min-h-[560px] overflow-hidden lg:grid-cols-[340px_1fr]">
    <aside class="{{ $active ? 'hidden lg:flex' : 'flex' }} min-h-0 flex-col border-r border-line">
        <div class="border-b border-line p-5"><h1 class="font-display text-2xl font-bold">Messages</h1><p class="text-sm text-muted">Private conversations tied to jobs and projects.</p></div>
        <div class="min-h-0 flex-1 space-y-1 overflow-y-auto p-2">
            @forelse($conversations as $c)
                @php $other = $c->otherParty(auth()->user()); $unread = $c->unreadFor(auth()->user()); @endphp
                <a href="{{ route('messages.show', $c) }}" class="flex items-center gap-3 rounded-xl p-3 {{ $active?->id === $c->id ? 'bg-brand-soft/70' : 'hover:bg-zinc-50' }}">
                    <x-avatar :user="$other" size="size-11"/>
                    <div class="min-w-0 flex-1">
                        <div class="flex justify-between gap-2"><p class="truncate font-semibold">{{ $other->name }}</p><span class="shrink-0 text-xs text-muted">{{ $c->latestMessage?->created_at?->diffForHumans(short: true) }}</span></div>
                        <p class="truncate text-xs text-muted">{{ $c->jobPost?->title }}</p>
                        <p class="truncate text-sm {{ $unread ? 'font-medium text-ink' : 'text-ink-soft' }}">{{ $c->latestMessage?->body ?? 'Say hello 👋' }}</p>
                    </div>
                    @if($unread)<span class="grid size-5 place-items-center rounded-full bg-brand text-[10px] text-white">{{ $unread }}</span>@endif
                </a>
            @empty
                <x-empty class="m-3" icon="message" title="No conversations" text="Clients open chats from applications; accepted invitations also start one."/>
            @endforelse
        </div>
    </aside>
    <section class="{{ $active ? 'flex' : 'hidden lg:flex' }} min-h-0 flex-col">
        @if($active)
            @php $other = $active->otherParty(auth()->user()); @endphp
            <header class="flex items-center gap-3 border-b border-line px-5 py-4">
                <a href="{{ route('messages.index') }}" class="lg:hidden" aria-label="Back"><x-icon name="chevron-left"/></a>
                <x-avatar :user="$other" size="size-10"/>
                <div class="min-w-0 flex-1"><p class="font-semibold">{{ $other->name }}</p><p class="truncate text-xs text-muted">{{ $active->jobPost?->title }}</p></div>
                @if($active->project)<a href="{{ route('projects.show', $active->project) }}" class="btn btn-sm btn-light"><x-icon name="folder" class="size-4"/> Project</a>
                @elseif($active->application)<a href="{{ route('applications.show', $active->application) }}#agreement" class="btn btn-sm btn-light"><x-icon name="file" class="size-4"/> Application &amp; agreement</a>@endif
            </header>
            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-zinc-50/50 p-5" x-init="$el.scrollTop = $el.scrollHeight">
                @foreach($messages as $m)
                    @php $mine = $m->user_id === auth()->id(); @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%]">
                            <div class="whitespace-pre-line rounded-2xl px-4 py-2.5 text-[15px] {{ $mine ? 'rounded-br-md bg-ink text-white' : 'rounded-bl-md bg-white ring-1 ring-line' }}">{{ $m->body }}</div>
                            <p class="mt-1 text-[11px] text-muted {{ $mine ? 'text-right' : '' }}">{{ $m->created_at->format('M j, H:i') }}@if($mine && $m->read_at) · Read @endif</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('messages.store', $active) }}" class="flex items-end gap-2 border-t border-line p-4">@csrf
                <label class="sr-only" for="body">Message</label>
                <textarea id="body" name="body" rows="1" required maxlength="4000" class="input max-h-40 min-h-11 flex-1 resize-none" placeholder="Write a message…" @keydown.enter.prevent.exact="$el.form.requestSubmit()"></textarea>
                <button class="btn btn-dark w-11 px-0" aria-label="Send"><x-icon name="send" class="size-4"/></button>
            </form>
        @else
            <div class="grid flex-1 place-items-center text-center text-muted"><div><x-icon name="message" class="mx-auto size-10 text-faint"/><p class="mt-3">Select a conversation</p></div></div>
        @endif
    </section>
</div>
</x-layouts.app>
