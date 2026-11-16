@php
    use App\Support\Money;
    $job = $application->jobPost;
    $fl = $application->freelancer;
    $current = $application->agreements->firstWhere('status', '!=', 'superseded');
    $closed = in_array($application->status, ['accepted', 'rejected', 'withdrawn']);
    $steps = ['submitted' => 'Submitted', 'viewed' => 'Viewed', 'shortlisted' => 'Shortlisted', 'discussion' => 'Discussion', 'negotiation' => 'Negotiation', 'accepted' => 'Accepted'];
    $order = array_keys($steps);
    $reached = array_search($application->status, $order);
    $prefill = $current ?? null;
@endphp
<x-layouts.app title="Application">
<section class="card card-pad">
    <a href="{{ $isClient ? route('client.jobs.applicants', $job) : route('applications.mine') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> {{ $isClient ? 'All applicants' : 'My proposals' }}</a>
    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar :user="$isClient ? $fl->user : $job->client->user" size="size-14"/>
            <div>
                <h1 class="font-display text-2xl font-bold">{{ $isClient ? $fl->user->name : $job->title }}</h1>
                <p class="text-muted">{{ $isClient ? ($fl->title.' · applied to “'.$job->title.'”') : $job->client->displayName() }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-status :status="$application->status" class="text-sm"/>
            @if($application->conversation)
                <a href="{{ route('messages.show', $application->conversation) }}" class="btn btn-light"><x-icon name="message" class="size-4"/> Open chat</a>
            @elseif($isClient && ! $closed)
                <form method="POST" action="{{ route('applications.conversation', $application) }}">@csrf<button class="btn btn-light"><x-icon name="message" class="size-4"/> Invite to chat</button></form>
            @endif
            @if($application->project)<a href="{{ route('projects.show', $application->project) }}" class="btn btn-dark"><x-icon name="folder" class="size-4"/> Project workspace</a>@endif
        </div>
    </div>

    {{-- Pipeline --}}
    @if(! in_array($application->status, ['rejected', 'withdrawn']))
        <ol class="mt-7 grid grid-cols-3 gap-2 sm:grid-cols-6">
            @foreach($steps as $key => $label)
                @php $i = $loop->index; $done = $reached !== false && $i <= $reached; @endphp
                <li class="flex flex-col gap-2"><span class="h-1.5 rounded-full {{ $done ? 'bg-ink' : 'bg-zinc-200' }}"></span><span class="text-xs {{ $done ? 'font-semibold text-ink' : 'text-muted' }}">{{ $label }}</span></li>
            @endforeach
        </ol>
    @endif
</section>

<div class="grid gap-3 sm:gap-4 xl:grid-cols-[1fr_360px]">
    <div class="space-y-3 sm:space-y-4">
        <section class="card card-pad">
            <h2 class="panel-title">Proposal</h2>
            <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Proposed price</dt><dd class="mt-0.5 font-semibold">{{ Money::format($application->proposed_amount_minor, $application->currency) }}</dd></div>
                <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Delivery</dt><dd class="mt-0.5 font-semibold">{{ $application->estimated_days }} days</dd></div>
                <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Availability</dt><dd class="mt-0.5 font-semibold">{{ $application->availability ?? '—' }}</dd></div>
            </dl>
            <p class="mt-5 whitespace-pre-line text-[15px] leading-relaxed text-ink-soft">{{ $application->cover_message }}</p>
        </section>

        @if($application->answers->isNotEmpty())
        <section class="card card-pad">
            <h2 class="panel-title">Screening answers</h2>
            <dl class="mt-4 divide-y divide-line">
                @foreach($application->answers as $ans)
                    <div class="py-3"><dt class="text-sm text-muted">{{ $ans->question?->prompt }}</dt>
                        <dd class="mt-1 font-medium">@if($ans->question?->type === 'portfolio_link')<a href="{{ $ans->answer }}" target="_blank" rel="noopener nofollow" class="underline">{{ $ans->answer }}</a>@else{{ $ans->display() }}@endif</dd></div>
                @endforeach
            </dl>
        </section>
        @endif

        @if($application->portfolioProjects->isNotEmpty())
        <section class="card card-pad">
            <h2 class="panel-title">Selected work</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">@foreach($application->portfolioProjects as $p)<x-portfolio-tile :project="$p->setRelation('profile', $fl)" :showAuthor="false"/>@endforeach</div>
        </section>
        @endif

        {{-- Agreement --}}
        <section class="card card-pad" id="agreement">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="panel-title">Project agreement</h2><p class="text-sm text-muted">Both parties must confirm the same version. Changing terms creates a new version.</p></div>
                @if($current)<x-status :status="$current->status"/>@endif
            </div>

            @if($current)
                <div class="mt-5 rounded-2xl border border-line">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3 text-sm">
                        <span class="font-semibold">Version {{ $current->version }}</span>
                        <span class="text-muted">by {{ $current->author->name }} · {{ $current->created_at->format('M j, Y H:i') }}</span>
                    </div>
                    <div class="space-y-4 p-5 text-sm">
                        <p class="font-display text-lg font-semibold">{{ $current->title }}</p>
                        <div><p class="text-muted">Scope</p><p class="mt-1 whitespace-pre-line">{{ $current->scope }}</p></div>
                        <div><p class="text-muted">Deliverables</p><p class="mt-1 whitespace-pre-line">{{ $current->deliverables }}</p></div>
                        <dl class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Agreed price</dt><dd class="font-semibold">{{ Money::format($current->amount_minor, $current->currency) }}</dd></div>
                            <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Deadline</dt><dd class="font-semibold">{{ $current->deadline->format('M j, Y') }}</dd></div>
                            <div class="rounded-xl bg-zinc-50 p-3"><dt class="text-muted">Revisions</dt><dd class="font-semibold">{{ $current->revisions }}</dd></div>
                        </dl>
                        @if($current->milestones)
                            <div><p class="text-muted">Milestones</p>
                                <ul class="mt-2 space-y-1.5">@foreach($current->milestones as $m)<li class="flex justify-between rounded-lg bg-zinc-50 px-3 py-2"><span>{{ $m['title'] }}</span><span class="text-muted">{{ $m['due_date'] ? \Illuminate\Support\Carbon::parse($m['due_date'])->format('M j') : '' }} {{ $m['amount_minor'] ? '· '.Money::format($m['amount_minor'], $current->currency) : '' }}</span></li>@endforeach</ul></div>
                        @endif
                        @if($current->cancellation_terms)<div><p class="text-muted">Cancellation terms</p><p class="mt-1 whitespace-pre-line">{{ $current->cancellation_terms }}</p></div>@endif
                        <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">The agreed price is recorded for reference only. The client pays the freelancer directly; the platform does not collect, hold or verify project payments.</p>
                    </div>
                    <div class="grid gap-3 border-t border-line p-5 sm:grid-cols-2">
                        @foreach([[$job->client->user, 'Client'], [$fl->user, 'Freelancer']] as [$party, $role])
                            @php $conf = $current->confirmations->firstWhere('user_id', $party->id); @endphp
                            <div class="flex items-center gap-3 rounded-xl {{ $conf ? 'bg-leaf-soft' : 'bg-zinc-50' }} p-3">
                                <x-icon :name="$conf ? 'check-circle' : 'clock'" class="size-5 {{ $conf ? 'text-leaf-dark' : 'text-muted' }}"/>
                                <div class="text-sm"><p class="font-semibold">{{ $role }} · {{ $party->name }}</p><p class="text-muted">{{ $conf ? 'Confirmed '.$conf->confirmed_at->format('M j, H:i') : 'Awaiting confirmation' }}</p></div>
                            </div>
                        @endforeach
                    </div>
                    @if($current->status === 'awaiting_confirmations' && ! $current->confirmedBy(auth()->user()))
                        <form method="POST" action="{{ route('agreements.confirm', $current) }}" class="border-t border-line p-5">@csrf
                            <input type="hidden" name="content_hash" value="{{ $current->content_hash }}">
                            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="acknowledge" value="1" class="checkbox mt-0.5" required> <span>I have read version {{ $current->version }} and agree to these terms. I understand payment happens directly between us, outside the platform.</span></label>
                            <button class="btn btn-dark mt-4 w-full sm:w-auto"><x-icon name="check" class="size-4"/> Confirm version {{ $current->version }}</button>
                        </form>
                    @endif
                </div>
            @else
                <p class="mt-5 rounded-xl bg-zinc-50 p-4 text-sm text-muted">No agreement yet. Discuss the scope first, then either party can draft one.</p>
            @endif

            @if(! $closed && in_array($application->status, ['shortlisted', 'discussion', 'negotiation']))
                <details class="mt-5 rounded-2xl border border-dashed border-zinc-300" @if(! $current) open @endif x-data="{ ms: @js($prefill?->milestones ? collect($prefill->milestones)->map(fn($m) => ['title' => $m['title'], 'due_date' => $m['due_date'], 'amount' => Money::toMajor($m['amount_minor'])])->values() : []) }">
                    <summary class="cursor-pointer px-5 py-4 font-semibold">{{ $current ? 'Propose changes (creates version '.($current->version + 1).')' : 'Draft agreement' }}</summary>
                    <form method="POST" action="{{ route('agreements.store', $application) }}" class="grid gap-4 px-5 pb-5 sm:grid-cols-2">@csrf
                        <div class="sm:col-span-2"><label class="label">Title</label><input name="title" class="input" required value="{{ old('title', $prefill->title ?? $job->title) }}"></div>
                        <div class="sm:col-span-2"><label class="label">Scope (included &amp; excluded)</label><textarea name="scope" rows="3" class="input" required>{{ old('scope', $prefill->scope ?? '') }}</textarea></div>
                        <div class="sm:col-span-2"><label class="label">Deliverables &amp; formats</label><textarea name="deliverables" rows="2" class="input" required>{{ old('deliverables', $prefill->deliverables ?? $job->deliverables) }}</textarea></div>
                        <div><label class="label">Agreed price</label><div class="flex"><input type="number" step="0.01" name="amount" class="input rounded-r-none" required value="{{ old('amount', Money::toMajor($prefill->amount_minor ?? $application->proposed_amount_minor)) }}"><select name="currency" class="input w-24 rounded-l-none border-l-0">@foreach(config('marketplace.currencies') as $c)<option @selected(($prefill->currency ?? $application->currency) === $c)>{{ $c }}</option>@endforeach</select></div></div>
                        <div class="grid grid-cols-2 gap-3"><div><label class="label">Deadline</label><input type="date" name="deadline" class="input" required value="{{ old('deadline', $prefill?->deadline?->format('Y-m-d') ?? now()->addDays($application->estimated_days)->format('Y-m-d')) }}"></div><div><label class="label">Revisions</label><input type="number" min="0" max="20" name="revisions" class="input" value="{{ old('revisions', $prefill->revisions ?? 2) }}"></div></div>
                        <div class="sm:col-span-2"><label class="label">Cancellation terms</label><textarea name="cancellation_terms" rows="2" class="input" placeholder="e.g. Either party may cancel with 3 days notice; completed milestones are paid.">{{ old('cancellation_terms', $prefill->cancellation_terms ?? '') }}</textarea></div>
                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between"><span class="label mb-0">Milestones (optional)</span><button type="button" class="btn btn-sm btn-light" @click="ms.push({title: '', due_date: '', amount: ''})"><x-icon name="plus" class="size-4"/> Add</button></div>
                            <template x-for="(m, i) in ms" :key="i">
                                <div class="mt-2 grid grid-cols-[1fr_140px_110px_auto] gap-2">
                                    <input class="input" :name="`milestones[${i}][title]`" x-model="m.title" placeholder="Milestone">
                                    <input type="date" class="input" :name="`milestones[${i}][due_date]`" x-model="m.due_date">
                                    <input type="number" step="0.01" class="input" :name="`milestones[${i}][amount]`" x-model="m.amount" placeholder="Amount">
                                    <button type="button" class="rounded-lg px-2 text-rose-600" @click="ms.splice(i, 1)" aria-label="Remove"><x-icon name="trash" class="size-4"/></button>
                                </div>
                            </template>
                        </div>
                        <div class="sm:col-span-2"><button class="btn btn-dark">Create agreement version</button></div>
                    </form>
                </details>
            @endif

            @if($application->agreements->count() > 1)
                <details class="mt-4 text-sm"><summary class="cursor-pointer text-muted">Version history ({{ $application->agreements->count() }})</summary>
                    <ul class="mt-2 space-y-1">@foreach($application->agreements as $v)<li class="flex justify-between rounded-lg bg-zinc-50 px-3 py-2"><span>v{{ $v->version }} · {{ Money::format($v->amount_minor, $v->currency) }} · due {{ $v->deadline->format('M j') }}</span><x-status :status="$v->status"/></li>@endforeach</ul>
                </details>
            @endif
        </section>
    </div>

    <aside class="space-y-3 sm:space-y-4">
        @if(! $closed)
        <section class="card card-pad">
            <h2 class="font-semibold">Actions</h2>
            <div class="mt-4 space-y-2">
                @if($isClient)
                    @if($application->canTransitionTo('shortlisted'))<form method="POST" action="{{ route('applications.status', $application) }}">@csrf<input type="hidden" name="status" value="shortlisted"><button class="btn btn-dark w-full"><x-icon name="star" class="size-4"/> Shortlist</button></form>@endif
                    @if($application->canTransitionTo('discussion') && ! $application->conversation)<form method="POST" action="{{ route('applications.conversation', $application) }}">@csrf<button class="btn btn-light w-full"><x-icon name="message" class="size-4"/> Invite to chat</button></form>@endif
                    <form method="POST" action="{{ route('applications.status', $application) }}" x-data="{ o: false }">@csrf<input type="hidden" name="status" value="rejected">
                        <button type="button" class="btn btn-danger w-full" @click="o = !o" x-show="!o">Reject</button>
                        <div x-show="o" x-cloak class="space-y-2"><input name="reason" class="input" placeholder="Optional reason shared with the applicant"><button class="btn btn-danger w-full">Confirm rejection</button></div>
                    </form>
                    <p class="text-xs text-muted">You make the final decision — nothing is rejected automatically.</p>
                @else
                    <form method="POST" action="{{ route('applications.status', $application) }}" onsubmit="return confirm('Withdraw this application?')">@csrf<input type="hidden" name="status" value="withdrawn"><button class="btn btn-danger w-full">Withdraw application</button></form>
                @endif
            </div>
        </section>
        @endif

        @if($isClient)
        <section class="card card-pad">
            <h2 class="font-semibold">Freelancer</h2>
            <div class="mt-3 flex flex-wrap gap-1.5">@foreach($fl->skills->take(8) as $s)<span class="chip">{{ $s->name }}</span>@endforeach</div>
            <a href="{{ route('freelancers.show', $fl) }}" class="btn btn-light mt-4 w-full">View full portfolio</a>
            <p class="mt-3 flex gap-2 text-xs text-muted"><x-icon name="lock" class="size-4 shrink-0"/> Contact and CBE/Telebirr details unlock only after both of you confirm the same agreement.</p>
        </section>
        @endif

        <section class="card card-pad">
            <h2 class="font-semibold">Status history</h2>
            <ol class="mt-4 space-y-3 border-l border-line pl-4 text-sm">
                @foreach($history as $h)
                    <li class="relative"><span class="absolute -left-[21px] top-1.5 size-2.5 rounded-full bg-ink ring-4 ring-white"></span><p class="font-medium">{{ \App\Support\Labels::status($h->to_status) }}</p><p class="text-xs text-muted">{{ $h->actor?->name ?? 'System' }} · {{ $h->created_at->format('M j, H:i') }}</p>@if($h->reason)<p class="mt-0.5 text-xs text-ink-soft">{{ $h->reason }}</p>@endif</li>
                @endforeach
                <li class="relative"><span class="absolute -left-[21px] top-1.5 size-2.5 rounded-full bg-zinc-300 ring-4 ring-white"></span><p class="font-medium">Submitted</p><p class="text-xs text-muted">{{ $application->created_at->format('M j, H:i') }}</p></li>
            </ol>
        </section>
    </aside>
</div>
</x-layouts.app>
