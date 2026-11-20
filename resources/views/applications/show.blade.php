@php
    use App\Support\Money;
    use Illuminate\Support\Str;
    $job = $application->jobPost;
    $fl = $application->freelancer;
    $current = $application->agreements->firstWhere('status', '!=', 'superseded');
    $closed = in_array($application->status, ['accepted', 'rejected', 'withdrawn']);
    $steps = ['submitted' => 'Submitted', 'viewed' => 'Viewed', 'shortlisted' => 'Shortlisted', 'discussion' => 'Discussion', 'negotiation' => 'Negotiation', 'accepted' => 'Hired'];
    $order = array_keys($steps);
    $reached = array_search($application->status, $order);
    $prefill = $current ?? null;
    $rating = $fl->rating();
    $max = $job->budget_max_minor ?: $job->budget_min_minor;
    $fit = null;
    if ($max && $application->currency === $job->currency) {
        $fit = $application->proposed_amount_minor < ($job->budget_min_minor ?: 0) ? ['Below budget', 'bg-sky-50 text-sky-700']
            : ($application->proposed_amount_minor <= $max ? ['Within budget', 'bg-leaf/10 text-leaf-dark'] : ['Above budget', 'bg-amber-50 text-amber-700']);
    }
    $next = match (true) {
        $application->status === 'accepted' => ['Hired', 'The agreement is confirmed. Work continues in the project workspace.'],
        in_array($application->status, ['rejected', 'withdrawn']) => [\App\Support\Labels::status($application->status), 'This application is closed.'],
        ! $isClient => ['Waiting on the client', 'You\'ll be notified when the client shortlists you or starts a chat.'],
        in_array($application->status, ['submitted', 'viewed']) => ['Decide on this proposal', 'Shortlist to keep them in the running, or start a chat to ask questions.'],
        $application->status === 'shortlisted' => ['Start the conversation', 'Invite them to chat and agree on scope before drafting an agreement.'],
        default => ['Agree on the terms', 'Draft an agreement below. Payment details unlock once you both confirm the same version.'],
    };
@endphp
<x-layouts.app title="{{ $isClient ? $fl->user->name.' · Proposal' : 'Your proposal' }}">

{{-- ───────── Header ───────── --}}
<section class="card overflow-hidden">
    <div class="bg-gradient-to-br from-zinc-50 via-white to-violet-50/60 px-5 pb-6 pt-5 sm:px-8 sm:pt-7">
        <a href="{{ $isClient ? route('client.jobs.applicants', $job) : route('applications.mine') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> {{ $isClient ? 'All applicants' : 'My proposals' }}</a>

        <div class="mt-5 flex flex-wrap items-start justify-between gap-5">
            <div class="flex min-w-0 items-center gap-4">
                <x-avatar :user="$isClient ? $fl->user : $job->client->user" size="size-16" class="text-lg"/>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-display text-2xl font-bold leading-tight tracking-tight sm:text-3xl">{{ $isClient ? $fl->user->name : $job->title }}</h1>
                        <x-status :status="$application->status"/>
                    </div>
                    @if($isClient)
                        <p class="mt-0.5 truncate text-ink-soft">{{ $fl->title }}</p>
                        <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-muted">
                            @if($rating['count'])<span class="inline-flex items-center gap-1 font-medium text-ink"><x-icon name="star" class="size-3.5 fill-amber-400 text-amber-400"/> {{ number_format($rating['avg'], 1) }} <span class="font-normal text-muted">({{ $rating['count'] }})</span></span>@endif
                            <span>{{ \App\Support\Labels::experience($fl->experience_level) }}</span>
                            @if($fl->location)<span class="inline-flex items-center gap-1"><x-icon name="pin" class="size-3.5"/> {{ $fl->location }}</span>@endif
                            <span>Applied to <a href="{{ route('jobs.show', $job) }}" class="font-medium text-ink hover:underline">{{ Str::limit($job->title, 48) }}</a></span>
                        </p>
                    @else
                        <p class="mt-1 text-[13px] text-muted">{{ $job->client->displayName() }} · submitted {{ $application->created_at->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($application->project)<a href="{{ route('projects.show', $application->project) }}" class="btn btn-dark btn-pill"><x-icon name="folder" class="size-4"/> Project workspace</a>@endif
                @if($application->conversation)
                    <a href="{{ route('messages.show', $application->conversation) }}" class="btn btn-light btn-pill"><x-icon name="message" class="size-4"/> Open chat</a>
                @elseif($isClient && ! $closed)
                    <form method="POST" action="{{ route('applications.conversation', $application) }}">@csrf<button class="btn btn-light btn-pill"><x-icon name="message" class="size-4"/> Invite to chat</button></form>
                @endif
                @if($isClient)<a href="{{ route('freelancers.show', $fl) }}" class="btn btn-light btn-pill"><x-icon name="user" class="size-4"/> Profile</a>@endif
            </div>
        </div>

        {{-- Pipeline --}}
        @if(! in_array($application->status, ['rejected', 'withdrawn']))
            <ol class="mt-8 flex items-center">
                @foreach($steps as $key => $label)
                    @php $i = $loop->index; $done = $reached !== false && $i <= $reached; $here = $reached === $i && $application->status !== 'accepted'; @endphp
                    <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                        <span class="flex flex-col items-center gap-1.5">
                            <span class="grid size-7 place-items-center rounded-full text-[11px] font-bold transition {{ $here ? 'bg-ink text-white ring-4 ring-ink/10' : ($done ? 'bg-ink text-white' : 'border border-line bg-white text-muted') }}">
                                @if($done && ! $here)<x-icon name="check" class="size-3.5"/>@else{{ $i + 1 }}@endif
                            </span>
                            <span class="hidden text-[11px] sm:block {{ $done ? 'font-semibold text-ink' : 'text-muted' }}">{{ $label }}</span>
                        </span>
                        @unless($loop->last)<span class="mx-1.5 h-0.5 flex-1 rounded-full sm:-mt-5 {{ $reached !== false && $i < $reached ? 'bg-ink' : 'bg-zinc-200' }}"></span>@endunless
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    {{-- Offer strip --}}
    <dl class="grid grid-cols-2 gap-px border-t border-line bg-line sm:grid-cols-4">
        <div class="col-span-2 bg-white px-5 py-4 sm:col-span-1 sm:px-8">
            <dt class="text-xs font-medium uppercase tracking-wider text-muted">Proposed price</dt>
            <dd class="mt-1 flex flex-wrap items-center gap-2"><span class="font-display text-2xl font-bold">{{ Money::format($application->proposed_amount_minor, $application->currency) }}</span>@if($fit)<span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $fit[1] }}">{{ $fit[0] }}</span>@endif</dd>
        </div>
        <div class="bg-white px-5 py-4 sm:px-6"><dt class="text-xs font-medium uppercase tracking-wider text-muted">Delivery</dt><dd class="mt-1.5 font-semibold">{{ $application->estimated_days }} {{ Str::plural('day', $application->estimated_days) }}</dd></div>
        <div class="bg-white px-5 py-4 sm:px-6"><dt class="text-xs font-medium uppercase tracking-wider text-muted">Can start</dt><dd class="mt-1.5 truncate font-semibold">{{ $application->availability ?? 'Not specified' }}</dd></div>
        <div class="col-span-2 bg-white px-5 py-4 sm:col-span-1 sm:px-6"><dt class="text-xs font-medium uppercase tracking-wider text-muted">Client budget</dt><dd class="mt-1.5 font-semibold">{{ $job->budgetLabel() }}</dd></div>
    </dl>
</section>

<div class="grid grid-cols-1 items-start gap-3 sm:gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="space-y-3 sm:space-y-4">
        <section class="card card-pad sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Cover letter</p>
            <div class="mt-4 max-w-3xl whitespace-pre-line border-l-2 border-ink/10 pl-5 text-[16px] leading-[1.75] text-ink-soft">{{ $application->cover_message }}</div>
        </section>

        @if($application->answers->isNotEmpty())
        <section class="card card-pad sm:p-8">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-xl font-bold tracking-tight">Screening answers</h2>
                <span class="text-sm text-muted">{{ $application->answers->count() }} of {{ $job->questions->count() }} answered</span>
            </div>
            <ol class="mt-5 space-y-3">
                @foreach($application->answers->sortBy(fn ($a) => $a->question?->sort_order ?? $a->id) as $ans)
                    @php $type = $ans->question?->type; $txt = $ans->display(); @endphp
                    <li class="rounded-2xl border border-line p-4 sm:p-5">
                        <p class="flex gap-3 text-sm text-muted"><span class="grid size-6 shrink-0 place-items-center rounded-md bg-zinc-100 text-[11px] font-bold text-ink-soft">{{ $loop->iteration }}</span><span class="pt-0.5">{{ $ans->question?->prompt }}</span></p>
                        <div class="mt-3 pl-9">
                            @if($type === 'yes_no')
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $txt === 'Yes' ? 'bg-leaf/10 text-leaf-dark' : 'bg-rose-50 text-rose-600' }}"><x-icon :name="$txt === 'Yes' ? 'check' : 'x'" class="size-4"/> {{ $txt }}</span>
                            @elseif($type === 'portfolio_link')
                                <a href="{{ $ans->answer }}" target="_blank" rel="noopener nofollow" class="inline-flex max-w-full items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm font-medium hover:bg-zinc-50"><x-icon name="link" class="size-4 shrink-0 text-muted"/> <span class="truncate">{{ $ans->answer }}</span> <x-icon name="arrow-up-right" class="size-4 shrink-0 text-muted"/></a>
                            @elseif(in_array($type, ['multi_select', 'multiple_choice']))
                                <span class="flex flex-wrap gap-1.5">@foreach(explode(', ', $txt) as $o)<span class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium">{{ $o }}</span>@endforeach</span>
                            @elseif($type === 'number')
                                <span class="font-display text-2xl font-bold">{{ $txt }}</span>
                            @elseif($type === 'file')
                                <span class="inline-flex items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm font-medium"><x-icon name="file" class="size-4 text-muted"/> File attached</span>
                            @else
                                <p class="whitespace-pre-line text-[15px] leading-relaxed text-ink">{{ $txt }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
        @endif

        @if($application->portfolioProjects->isNotEmpty())
        <section class="card card-pad sm:p-8">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-xl font-bold tracking-tight">Selected work</h2>
                <a href="{{ route('freelancers.show', $fl) }}" class="text-sm font-medium text-muted hover:text-ink">Full portfolio <x-icon name="arrow-right" class="inline size-4"/></a>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-3">@foreach($application->portfolioProjects as $p)<x-portfolio-tile :project="$p->setRelation('profile', $fl)" :showAuthor="false"/>@endforeach</div>
        </section>
        @endif
        {{-- Agreement --}}
        <section class="card card-pad scroll-mt-24 sm:p-8" id="agreement">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-display text-xl font-bold tracking-tight">Project agreement</h2><p class="text-sm text-muted">Both parties must confirm the same version. Changing terms creates a new version.</p></div>
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

    <aside class="space-y-3 sm:space-y-4 xl:sticky xl:top-20">
        <section class="card card-pad">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Next step</p>
            <p class="mt-2 font-display text-lg font-bold leading-snug">{{ $next[0] }}</p>
            <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $next[1] }}</p>
            @if(! $closed)
            <div class="mt-5 space-y-2">
                @if($isClient)
                    @if($application->canTransitionTo('shortlisted') && $application->status !== 'shortlisted')<form method="POST" action="{{ route('applications.status', $application) }}">@csrf<input type="hidden" name="status" value="shortlisted"><button class="btn btn-dark h-11 w-full"><x-icon name="star" class="size-4"/> Shortlist</button></form>@endif
                    @if(! $application->conversation)<form method="POST" action="{{ route('applications.conversation', $application) }}">@csrf<button class="btn {{ $application->status === 'shortlisted' ? 'btn-dark' : 'btn-light' }} h-11 w-full"><x-icon name="message" class="size-4"/> Invite to chat</button></form>
                    @else<a href="{{ route('messages.show', $application->conversation) }}" class="btn btn-light h-11 w-full"><x-icon name="message" class="size-4"/> Open chat</a>@endif
                    @if(in_array($application->status, ['shortlisted', 'discussion', 'negotiation']))<a href="#agreement" class="btn btn-light h-11 w-full"><x-icon name="file" class="size-4"/> {{ $current ? 'Review agreement' : 'Draft agreement' }}</a>@endif
                    <form method="POST" action="{{ route('applications.status', $application) }}" x-data="{ o: false }" class="pt-1">@csrf<input type="hidden" name="status" value="rejected">
                        <button type="button" class="w-full rounded-xl py-2 text-sm font-medium text-rose-600 hover:bg-rose-50" @click="o = !o" x-show="!o">Reject this proposal</button>
                        <div x-show="o" x-cloak class="space-y-2 rounded-xl bg-rose-50/60 p-3"><input name="reason" class="input" placeholder="Optional note shared with the applicant"><div class="flex gap-2"><button type="button" class="btn btn-light flex-1" @click="o = false">Cancel</button><button class="btn btn-danger flex-1">Reject</button></div></div>
                    </form>
                @else
                    @if($application->conversation)<a href="{{ route('messages.show', $application->conversation) }}" class="btn btn-dark h-11 w-full"><x-icon name="message" class="size-4"/> Open chat</a>@endif
                    <a href="{{ route('jobs.show', $job) }}" class="btn btn-light h-11 w-full"><x-icon name="eye" class="size-4"/> View job post</a>
                    <form method="POST" action="{{ route('applications.status', $application) }}" onsubmit="return confirm('Withdraw this application?')" class="pt-1">@csrf<input type="hidden" name="status" value="withdrawn"><button class="w-full rounded-xl py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Withdraw application</button></form>
                @endif
            </div>
            @endif
        </section>

        @if($isClient)
        <section class="card card-pad">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">About {{ Str::before($fl->user->name, ' ') }}</p>
            <dl class="mt-3 divide-y divide-line text-sm">
                <div class="flex justify-between py-2.5"><dt class="text-muted">Experience</dt><dd class="font-medium">{{ $fl->years_experience ? $fl->years_experience.' yrs · ' : '' }}{{ \App\Support\Labels::experience($fl->experience_level) }}</dd></div>
                @if($fl->hourly_rate_minor)<div class="flex justify-between py-2.5"><dt class="text-muted">Usual rate</dt><dd class="font-medium">{{ Money::format($fl->hourly_rate_minor, $fl->currency) }}/hr</dd></div>@endif
                <div class="flex justify-between py-2.5"><dt class="text-muted">Reviews</dt><dd class="font-medium">{{ $rating['count'] ? number_format($rating['avg'], 1).' ★ ('.$rating['count'].')' : 'None yet' }}</dd></div>
            </dl>
            @if($fl->skills->isNotEmpty())<div class="mt-3 flex flex-wrap gap-1.5">@foreach($fl->skills->take(8) as $s)<span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-ink-soft">{{ $s->name }}</span>@endforeach</div>@endif
            <a href="{{ route('freelancers.show', $fl) }}" class="btn btn-light mt-4 w-full">View full portfolio</a>
        </section>
        @endif

        <section class="card card-pad">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Activity</p>
            <ol class="mt-4 space-y-4 border-l border-line pl-5 text-sm">
                @foreach($history as $h)
                    <li class="relative"><span class="absolute -left-[25px] top-1 size-2.5 rounded-full {{ $loop->first ? 'bg-ink' : 'bg-zinc-300' }} ring-4 ring-white"></span><p class="font-medium">{{ \App\Support\Labels::status($h->to_status) }}</p><p class="text-xs text-muted">{{ $h->actor?->name ?? 'System' }} · {{ $h->created_at->format('M j, H:i') }}</p>@if($h->reason)<p class="mt-1 rounded-lg bg-zinc-50 px-2.5 py-1.5 text-xs text-ink-soft">“{{ $h->reason }}”</p>@endif</li>
                @endforeach
                <li class="relative"><span class="absolute -left-[25px] top-1 size-2.5 rounded-full bg-zinc-300 ring-4 ring-white"></span><p class="font-medium">Submitted</p><p class="text-xs text-muted">{{ $application->created_at->format('M j, H:i') }}</p></li>
            </ol>
        </section>
    </aside>
</div>
</x-layouts.app>
