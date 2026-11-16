@php use App\Support\Money; @endphp
<x-layouts.app title="Applicants">
<section class="card card-pad">
    <a href="{{ route('client.jobs.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> My jobs</a>
    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div><h1 class="font-display text-3xl font-bold">{{ $job->title }}</h1><p class="mt-1 text-muted">{{ $counts->sum() }} applicants · {{ $job->budgetLabel() }} · <x-status :status="$job->status"/></p></div>
        <div class="flex rounded-xl border border-line bg-zinc-50 p-1">
            <a href="{{ request()->fullUrlWithQuery(['view' => 'cards']) }}" class="flex h-10 items-center gap-2 rounded-lg px-3 text-sm {{ $view !== 'table' ? 'bg-white shadow-sm ring-1 ring-line' : 'text-muted' }}"><x-icon name="grid" class="size-4"/> Cards</a>
            <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="flex h-10 items-center gap-2 rounded-lg px-3 text-sm {{ $view === 'table' ? 'bg-white shadow-sm ring-1 ring-line' : 'text-muted' }}"><x-icon name="list" class="size-4"/> Compare answers</a>
        </div>
    </div>
    <form method="GET" class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-[repeat(5,1fr)_auto]">
        <input type="hidden" name="view" value="{{ $view }}">
        <select name="status" class="input" aria-label="Status"><option value="">All statuses</option>@foreach(\App\Models\Application::STATUSES as $s)<option value="{{ $s }}" @selected(($f['status'] ?? '') === $s)>{{ \App\Support\Labels::status($s) }} ({{ $counts[$s] ?? 0 }})</option>@endforeach</select>
        <input name="skill" value="{{ $f['skill'] ?? '' }}" class="input" placeholder="Skill">
        <input name="max_budget" type="number" value="{{ $f['max_budget'] ?? '' }}" class="input" placeholder="Max price">
        <select name="level" class="input" aria-label="Experience"><option value="">Any experience</option>@foreach(['entry' => 'Entry', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)<option value="{{ $v }}" @selected(($f['level'] ?? '') === $v)>{{ $l }}</option>@endforeach</select>
        <select name="sort" class="input" aria-label="Sort">@foreach(['newest' => 'Newest', 'price_low' => 'Lowest price', 'price_high' => 'Highest price', 'fastest' => 'Fastest delivery'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'newest') === $v)>{{ $l }}</option>@endforeach</select>
        <button class="btn btn-dark"><x-icon name="filter" class="size-4"/> Filter</button>
    </form>
</section>

@if($applications->isEmpty())
    <x-empty icon="users" title="No applicants match" text="{{ $counts->sum() ? 'Adjust the filters to see more.' : 'Share your job or invite freelancers from the directory.' }}"><a href="{{ route('freelancers.index', ['category' => $job->category?->slug]) }}" class="btn btn-dark">Find freelancers to invite</a></x-empty>
@elseif($view === 'table')
    <section class="card overflow-x-auto">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b border-line bg-zinc-50/70"><tr>
                <th class="table-head px-5 py-3">Applicant</th><th class="table-head px-4 py-3">Price</th><th class="table-head px-4 py-3">Days</th><th class="table-head px-4 py-3">Status</th>
                @foreach($job->questions as $q)<th class="table-head max-w-[200px] px-4 py-3 normal-case" title="{{ $q->prompt }}">{{ \Illuminate\Support\Str::limit($q->prompt, 40) }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-line">
                @foreach($applications as $a)
                    <tr class="hover:bg-zinc-50">
                        <td class="px-5 py-3"><a href="{{ route('applications.show', $a) }}" class="flex items-center gap-3"><x-avatar :user="$a->freelancer->user" size="size-9" class="text-xs"/><span><span class="block font-semibold">{{ $a->freelancer->user->name }}</span><span class="text-xs text-muted">{{ \App\Support\Labels::experience($a->freelancer->experience_level) }}</span></span></a></td>
                        <td class="px-4 py-3 font-medium">{{ Money::format($a->proposed_amount_minor, $a->currency) }}</td>
                        <td class="px-4 py-3">{{ $a->estimated_days }}</td>
                        <td class="px-4 py-3"><x-status :status="$a->status"/></td>
                        @foreach($job->questions as $q)
                            @php $ans = $a->answers->firstWhere('screening_question_id', $q->id); @endphp
                            <td class="max-w-[200px] px-4 py-3 align-top text-ink-soft"><span class="line-clamp-3">{{ $ans ? $ans->display() : '—' }}</span></td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@else
    <div class="grid gap-3 sm:gap-4 xl:grid-cols-2">
        @foreach($applications as $a)
            @php $fl = $a->freelancer; @endphp
            <article class="card flex flex-col p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <a href="{{ route('applications.show', $a) }}" class="flex items-center gap-3">
                        <x-avatar :user="$fl->user" size="size-12"/>
                        <div><p class="font-display text-lg font-bold">{{ $fl->user->name }}</p><p class="text-sm text-muted">{{ $fl->title }}</p></div>
                    </a>
                    <x-status :status="$a->status"/>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px]">
                    <span class="meta"><x-icon name="dollar" class="size-4 text-violet"/><span class="font-semibold text-ink">{{ Money::format($a->proposed_amount_minor, $a->currency) }}</span></span><span class="divider-dot"></span>
                    <span class="meta"><x-icon name="clock" class="size-4"/>{{ $a->estimated_days }} days</span><span class="divider-dot"></span>
                    <span class="meta"><x-icon name="user" class="size-4"/>{{ \App\Support\Labels::experience($fl->experience_level) }}</span>
                    @if($a->availability)<span class="divider-dot"></span><span class="meta">{{ $a->availability }}</span>@endif
                </div>
                <p class="mt-3 line-clamp-3 text-[15px] leading-relaxed text-muted">{{ $a->cover_message }}</p>
                @if($a->portfolioProjects->isNotEmpty())
                    <div class="mt-4 flex gap-2">@foreach($a->portfolioProjects->take(4) as $p)<a href="{{ route('portfolio.show', $p) }}" class="size-16 overflow-hidden rounded-xl bg-zinc-100" title="{{ $p->title }}">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->title }}" class="size-full object-cover">@endif</a>@endforeach</div>
                @endif
                <div class="mt-4 flex flex-wrap gap-1.5">@foreach($fl->skills->take(5) as $s)<span class="chip">{{ $s->name }}</span>@endforeach</div>
                <div class="mt-auto flex flex-wrap items-center justify-end gap-2 pt-5">
                    @if($a->canTransitionTo('rejected'))<form method="POST" action="{{ route('applications.status', $a) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-ghost text-rose-600">Reject</button></form>@endif
                    @if($a->canTransitionTo('shortlisted') && ! in_array($a->status, ['discussion']))<form method="POST" action="{{ route('applications.status', $a) }}">@csrf<input type="hidden" name="status" value="shortlisted"><button class="btn btn-sm btn-light"><x-icon name="star" class="size-4"/> Shortlist</button></form>@endif
                    <a href="{{ route('applications.show', $a) }}" class="btn btn-sm btn-dark">Review</a>
                </div>
            </article>
        @endforeach
    </div>
@endif
</x-layouts.app>
