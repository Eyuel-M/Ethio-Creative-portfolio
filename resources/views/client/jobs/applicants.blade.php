@php
    use App\Support\Money;
    use Illuminate\Support\Str;
    $total = $counts->sum();
    $tabs = [
        '' => ['All', $total],
        'submitted' => ['New', $counts['submitted'] ?? 0],
        'viewed' => ['Viewed', $counts['viewed'] ?? 0],
        'shortlisted' => ['Shortlisted', $counts['shortlisted'] ?? 0],
        'discussion' => ['In discussion', ($counts['discussion'] ?? 0)],
        'negotiation' => ['Negotiating', $counts['negotiation'] ?? 0],
        'accepted' => ['Hired', $counts['accepted'] ?? 0],
        'rejected' => ['Rejected', $counts['rejected'] ?? 0],
    ];
    $active = $f['status'] ?? '';
    $max = $job->budget_max_minor ?: $job->budget_min_minor;
    $fit = function ($a) use ($job, $max) {
        if (! $max || $a->currency !== $job->currency) return null;
        if ($a->proposed_amount_minor < ($job->budget_min_minor ?: 0)) return ['Below budget', 'bg-sky-50 text-sky-700'];
        if ($a->proposed_amount_minor <= $max) return ['Within budget', 'bg-leaf/10 text-leaf-dark'];
        return ['Above budget', 'bg-amber-50 text-amber-700'];
    };
    $statusAction = fn ($a, $to) => $a->canTransitionTo($to);
    $quickTypes = ['yes_no', 'number', 'multiple_choice'];
@endphp
<x-layouts.app title="Applicants · {{ $job->title }}">

{{-- ───────── Header ───────── --}}
<section class="card overflow-hidden">
    <div class="bg-gradient-to-br from-zinc-50 via-white to-violet-50/60 px-5 pb-6 pt-5 sm:px-8 sm:pt-7">
        <a href="{{ route('client.jobs.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> My jobs</a>
        <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status :status="$job->status"/>
                    @if($job->category)<span class="text-xs font-medium text-muted">{{ $job->category->name }}</span>@endif
                    @if($job->published_at)<span class="text-xs text-faint">·</span><span class="text-xs text-muted">Posted {{ $job->published_at->diffForHumans() }}</span>@endif
                </div>
                <h1 class="mt-2 max-w-4xl font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">{{ $job->title }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-light btn-pill"><x-icon name="eye" class="size-4"/> View post</a>
                <a href="{{ route('freelancers.index', ['category' => $job->category?->slug]) }}" class="btn btn-dark btn-pill"><x-icon name="users" class="size-4"/> Invite talent</a>
            </div>
        </div>
    </div>
    <dl class="grid grid-cols-2 gap-px border-t border-line bg-line sm:grid-cols-5">
        @foreach([
            ['Applicants', $total],
            ['New', $counts['submitted'] ?? 0],
            ['Shortlisted', $counts['shortlisted'] ?? 0],
            ['Avg. price', $stats?->price ? Money::format((int) $stats->price, $job->currency) : '—'],
            ['Avg. delivery', $stats?->days ? round($stats->days).' days' : '—'],
        ] as [$k, $v])
            <div class="bg-white px-5 py-4 sm:px-6 {{ $loop->first ? 'col-span-2 sm:col-span-1 sm:px-8' : '' }}">
                <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ $k }}</dt>
                <dd class="mt-1 font-display text-xl font-bold">{{ $v }}</dd>
            </div>
        @endforeach
    </dl>
</section>

{{-- ───────── Pipeline tabs + tools ───────── --}}
<section class="card">
    <nav class="flex gap-1 overflow-x-auto border-b border-line px-3 sm:px-5" aria-label="Pipeline">
        @foreach($tabs as $key => [$label, $n])
            @continue($key !== '' && $key !== 'submitted' && $key !== 'shortlisted' && ! $n)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key ?: null]) }}" @if($active === $key) aria-current="page" @endif
               class="flex shrink-0 items-center gap-2 border-b-2 px-3 py-3.5 text-sm font-medium transition {{ $active === $key ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-ink' }}">
                {{ $label }} <span class="rounded-full px-1.5 py-px text-[11px] tabular-nums {{ $active === $key ? 'bg-ink text-white' : 'bg-zinc-100 text-ink-soft' }}">{{ $n }}</span>
            </a>
        @endforeach
    </nav>
    <form method="GET" class="flex flex-wrap items-center gap-2 p-3 sm:px-5">
        @if($active)<input type="hidden" name="status" value="{{ $active }}">@endif
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="relative min-w-[180px] flex-1 sm:max-w-xs"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/><input name="skill" value="{{ $f['skill'] ?? '' }}" class="input h-10 pl-9" placeholder="Filter by skill"></div>
        <input name="max_budget" type="number" min="0" value="{{ $f['max_budget'] ?? '' }}" class="input h-10 w-36" placeholder="Max price">
        <div class="w-40"><select name="level" class="input h-10" aria-label="Experience" onchange="this.form.submit()"><option value="">Any experience</option>@foreach(['entry' => 'Entry', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)<option value="{{ $v }}" @selected(($f['level'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="w-44"><select name="sort" class="input h-10" aria-label="Sort" onchange="this.form.submit()">@foreach(['newest' => 'Newest first', 'price_low' => 'Lowest price', 'price_high' => 'Highest price', 'fastest' => 'Fastest delivery'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'newest') === $v)>{{ $l }}</option>@endforeach</select></div>
        <button class="btn btn-light h-10"><x-icon name="filter" class="size-4"/> Apply</button>
        <div class="ml-auto flex rounded-full border border-line bg-zinc-50 p-1">
            <a href="{{ request()->fullUrlWithQuery(['view' => 'cards']) }}" class="flex h-8 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium {{ $view !== 'table' ? 'bg-white shadow-sm ring-1 ring-line' : 'text-muted hover:text-ink' }}"><x-icon name="grid" class="size-4"/> Proposals</a>
            <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="flex h-8 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium {{ $view === 'table' ? 'bg-white shadow-sm ring-1 ring-line' : 'text-muted hover:text-ink' }}"><x-icon name="list" class="size-4"/> Compare answers</a>
        </div>
    </form>
</section>

@if($applications->isEmpty())
    <x-empty icon="users" title="{{ $total ? 'No applicants match' : 'No applicants yet' }}" text="{{ $total ? 'Try another stage or clear the filters.' : 'Share your job or invite freelancers from the directory.' }}"><a href="{{ route('freelancers.index', ['category' => $job->category?->slug]) }}" class="btn btn-dark">Find freelancers to invite</a></x-empty>

@elseif($view === 'table')
    {{-- ───────── Compare answers ───────── --}}
    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] border-separate border-spacing-0 text-sm">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="sticky left-0 z-10 w-64 border-b border-r border-line bg-zinc-50 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Applicant</th>
                        <th class="border-b border-line px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Price</th>
                        <th class="border-b border-line px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Delivery</th>
                        @foreach($job->questions as $q)
                            <th class="min-w-[200px] max-w-[260px] border-b border-line px-4 py-3 text-left align-bottom font-normal" title="{{ $q->prompt }}">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-muted">Q{{ $loop->iteration }}</span>
                                <span class="mt-0.5 line-clamp-2 block text-[13px] font-medium leading-snug text-ink">{{ $q->prompt }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $a)
                        @php $fitInfo = $fit($a); @endphp
                        <tr class="group">
                            <td class="sticky left-0 z-10 border-b border-r border-line bg-white px-5 py-4 align-top group-hover:bg-zinc-50">
                                <a href="{{ route('applications.show', $a) }}" class="flex items-center gap-3">
                                    <x-avatar :user="$a->freelancer->user" size="size-9" class="text-xs"/>
                                    <span class="min-w-0"><span class="block truncate font-semibold hover:underline">{{ $a->freelancer->user->name }}</span><x-status :status="$a->status" class="mt-0.5"/></span>
                                </a>
                            </td>
                            <td class="border-b border-line px-4 py-4 align-top group-hover:bg-zinc-50">
                                <span class="whitespace-nowrap font-semibold">{{ Money::format($a->proposed_amount_minor, $a->currency) }}</span>
                                @if($fitInfo)<span class="mt-1 block w-fit whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium {{ $fitInfo[1] }}">{{ $fitInfo[0] }}</span>@endif
                            </td>
                            <td class="whitespace-nowrap border-b border-line px-4 py-4 align-top group-hover:bg-zinc-50">{{ $a->estimated_days }} days</td>
                            @foreach($job->questions as $q)
                                @php $ans = $a->answers->firstWhere('screening_question_id', $q->id); $txt = $ans?->display(); @endphp
                                <td class="border-b border-line px-4 py-4 align-top text-ink-soft group-hover:bg-zinc-50">
                                    @if($txt === null || $txt === '')
                                        <span class="text-faint">—</span>
                                    @elseif($q->type === 'yes_no')
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $txt === 'Yes' ? 'bg-leaf/10 text-leaf-dark' : 'bg-rose-50 text-rose-600' }}"><x-icon :name="$txt === 'Yes' ? 'check' : 'x'" class="size-3"/> {{ $txt }}</span>
                                    @elseif($q->type === 'portfolio_link')
                                        <a href="{{ $txt }}" target="_blank" rel="noopener nofollow" class="inline-flex max-w-full items-center gap-1 truncate text-brand hover:underline"><x-icon name="link" class="size-3.5 shrink-0"/> {{ Str::of($txt)->after('://')->limit(28) }}</a>
                                    @elseif($q->type === 'file')
                                        <span class="inline-flex items-center gap-1 text-ink"><x-icon name="file" class="size-3.5"/> File attached</span>
                                    @elseif($q->type === 'multi_select')
                                        <span class="flex flex-wrap gap-1">@foreach(explode(', ', $txt) as $o)<span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-xs text-ink">{{ $o }}</span>@endforeach</span>
                                    @else
                                        <span class="line-clamp-3 leading-relaxed {{ $q->type === 'number' ? 'font-semibold text-ink' : '' }}">{{ $txt }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

@else
    {{-- ───────── Proposal cards ───────── --}}
    <div class="space-y-3 sm:space-y-4">
        @foreach($applications as $a)
            @php
                $fl = $a->freelancer;
                $r = $ratings[$fl->user_id] ?? null;
                $fitInfo = $fit($a);
                $quick = $job->questions->whereIn('type', $quickTypes)->take(3)->map(fn ($q) => [$q, $a->answers->firstWhere('screening_question_id', $q->id)?->display()])->filter(fn ($p) => filled($p[1]));
            @endphp
            <article class="card overflow-hidden transition hover:shadow-[0_8px_30px_-12px_rgba(12,12,16,.18)]">
                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div class="p-5 sm:p-6">
                        {{-- Who --}}
                        <div class="flex items-start gap-4">
                            <a href="{{ route('applications.show', $a) }}" class="relative shrink-0">
                                <x-avatar :user="$fl->user" size="size-14"/>
                                @if($a->status === 'submitted')<span class="absolute -right-0.5 -top-0.5 size-3.5 rounded-full bg-brand ring-2 ring-white" title="New"></span>@endif
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <a href="{{ route('applications.show', $a) }}" class="font-display text-lg font-bold hover:underline">{{ $fl->user->name }}</a>
                                    @if($a->status === 'submitted')<span class="rounded-full bg-brand/10 px-2 py-0.5 text-[11px] font-semibold text-brand">New</span>@else<x-status :status="$a->status"/>@endif
                                </div>
                                <p class="truncate text-sm text-ink-soft">{{ $fl->title }}</p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                                    @if($r && $r->c)<span class="inline-flex items-center gap-1 font-medium text-ink"><x-icon name="star" class="size-3.5 fill-amber-400 text-amber-400"/> {{ number_format($r->avg, 1) }} <span class="font-normal text-muted">({{ $r->c }})</span></span>@endif
                                    <span>{{ \App\Support\Labels::experience($fl->experience_level) }}</span>
                                    @if($fl->location)<span class="inline-flex items-center gap-1"><x-icon name="pin" class="size-3.5"/> {{ $fl->location }}</span>@endif
                                    <span>Applied {{ $a->created_at->diffForHumans() }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- Pitch --}}
                        <p class="mt-4 line-clamp-3 text-[15px] leading-relaxed text-ink-soft">{{ $a->cover_message }}</p>

                        {{-- Quick answers --}}
                        @if($quick->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($quick as [$q, $txt])
                                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-lg bg-zinc-50 px-2.5 py-1.5 text-xs ring-1 ring-inset ring-line" title="{{ $q->prompt }}">
                                        <span class="truncate text-muted">{{ Str::limit($q->prompt, 34) }}</span>
                                        <span class="font-semibold {{ $txt === 'Yes' ? 'text-leaf-dark' : ($txt === 'No' ? 'text-rose-600' : 'text-ink') }}">{{ Str::limit($txt, 20) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Work --}}
                        @if($a->portfolioProjects->isNotEmpty())
                            <div class="mt-5 flex gap-2 overflow-x-auto">
                                @foreach($a->portfolioProjects->take(4) as $p)
                                    <a href="{{ route('portfolio.show', $p) }}" class="group/w block h-24 w-32 shrink-0 overflow-hidden rounded-xl bg-zinc-100 sm:h-28 sm:w-40" title="{{ $p->title }}">
                                        @if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->title }}" class="size-full object-cover transition duration-300 group-hover/w:scale-[1.04]" loading="lazy">@endif
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($fl->skills->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-1.5">@foreach($fl->skills->take(5) as $s)<span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-ink-soft">{{ $s->name }}</span>@endforeach</div>
                        @endif
                    </div>

                    {{-- Offer + actions --}}
                    <div class="flex flex-col border-t border-line bg-zinc-50/60 p-5 sm:p-6 lg:border-l lg:border-t-0">
                        <p class="text-xs font-medium uppercase tracking-wider text-muted">Proposed</p>
                        <p class="mt-1 font-display text-2xl font-bold">{{ Money::format($a->proposed_amount_minor, $a->currency) }}</p>
                        @if($fitInfo)<span class="mt-1.5 w-fit rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $fitInfo[1] }}">{{ $fitInfo[0] }}</span>@endif
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-2"><dt class="flex items-center gap-1.5 text-muted"><x-icon name="clock" class="size-4"/> Delivery</dt><dd class="font-medium">{{ $a->estimated_days }} {{ Str::plural('day', $a->estimated_days) }}</dd></div>
                            @if($a->availability)<div class="flex items-center justify-between gap-2"><dt class="flex items-center gap-1.5 text-muted"><x-icon name="calendar" class="size-4"/> Starts</dt><dd class="truncate font-medium">{{ $a->availability }}</dd></div>@endif
                            <div class="flex items-center justify-between gap-2"><dt class="flex items-center gap-1.5 text-muted"><x-icon name="layers" class="size-4"/> Work samples</dt><dd class="font-medium">{{ $a->portfolioProjects->count() }}</dd></div>
                        </dl>
                        <div class="mt-5 flex flex-col gap-2 lg:mt-auto lg:pt-5">
                            <a href="{{ route('applications.show', $a) }}" class="btn btn-dark h-10 w-full">Review proposal</a>
                            <div class="flex gap-2">
                                @if($statusAction($a, 'shortlisted') && $a->status !== 'shortlisted')
                                    <form method="POST" action="{{ route('applications.status', $a) }}" class="flex-1">@csrf<input type="hidden" name="status" value="shortlisted"><button class="btn btn-light h-10 w-full"><x-icon name="star" class="size-4"/> Shortlist</button></form>
                                @elseif($a->conversation)
                                    <a href="{{ route('messages.show', $a->conversation) }}" class="btn btn-light h-10 flex-1"><x-icon name="message" class="size-4"/> Chat</a>
                                @endif
                                @if($statusAction($a, 'rejected'))
                                    <form method="POST" action="{{ route('applications.status', $a) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-light h-10 w-10 px-0 text-rose-600 hover:bg-rose-50" title="Reject" aria-label="Reject {{ $fl->user->name }}"><x-icon name="x" class="size-4"/></button></form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
</x-layouts.app>
