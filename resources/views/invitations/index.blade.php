<x-layouts.app title="Invitations">
<section class="card card-pad"><h1 class="font-display text-3xl font-bold">Invitations</h1><p class="mt-1 text-muted">Clients who found your portfolio and want to talk. Accepting opens a conversation — it is not accepting the project.</p></section>
@if($invitations->isEmpty())
    <x-empty icon="sparkles" title="No invitations yet" text="Keep your portfolio fresh — clients invite creatives whose work stands out."><a href="{{ route('portfolio.index') }}" class="btn btn-dark">Improve portfolio</a></x-empty>
@else
    <div class="grid gap-4 xl:grid-cols-2">
        @foreach($invitations as $inv)
            <article class="card flex flex-col p-6">
                <div class="flex items-start justify-between gap-3"><div class="flex items-center gap-3"><x-avatar :user="$inv->jobPost->client->user" size="size-11"/><div><p class="font-semibold">{{ $inv->jobPost->client->displayName() }}</p><p class="text-xs text-muted">{{ $inv->created_at->diffForHumans() }}</p></div></div><x-status :status="$inv->status"/></div>
                <h2 class="mt-4 font-display text-lg font-bold"><a href="{{ route('jobs.show', $inv->jobPost) }}" class="hover:underline">{{ $inv->jobPost->title }}</a></h2>
                <p class="text-sm text-muted">{{ $inv->jobPost->budgetLabel() }}</p>
                @if($inv->message)<p class="mt-3 rounded-xl bg-zinc-50 p-3 text-sm text-ink-soft">“{{ $inv->message }}”</p>@endif
                @if($inv->respond_by)<p class="mt-2 text-xs text-muted">Please respond by {{ $inv->respond_by->format('M j') }}</p>@endif
                @if($inv->status === 'pending')
                    <div class="mt-auto flex gap-2 pt-5">
                        <form method="POST" action="{{ route('invitations.respond', $inv) }}">@csrf<input type="hidden" name="response" value="declined"><button class="btn btn-light">Decline</button></form>
                        <form method="POST" action="{{ route('invitations.respond', $inv) }}" class="flex-1">@csrf<input type="hidden" name="response" value="accepted"><button class="btn btn-dark w-full">Accept &amp; discuss</button></form>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
    {{ $invitations->links() }}
@endif
</x-layouts.app>
