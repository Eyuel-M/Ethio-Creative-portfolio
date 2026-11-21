<x-shell title="Find Projects">
<div x-data @change="if ($event.target.dataset.auto !== undefined) document.getElementById('jobfilters').requestSubmit()">
<form method="GET" action="{{ route('jobs.index') }}" id="jobfilters">
    <input type="hidden" name="view" value="{{ $f['view'] ?? 'grid' }}">
    {{-- Search header --}}
    <section class="card card-pad">
        <h1 class="font-display text-3xl font-bold tracking-tight sm:text-[34px]">Find Creative Projects Here!</h1>
        <p class="mt-2 text-[15px] text-ink-soft">Explore our newest project opportunities and apply for the top positions available today.</p>
        <div class="mt-6 flex flex-col gap-3 md:flex-row">
            <div class="flex flex-1 flex-col overflow-hidden rounded-xl border border-line bg-zinc-50/70 md:flex-row">
                <label class="flex flex-1 items-center gap-3 px-4" x-data="{ q: @js($f['q'] ?? '') }">
                    <x-icon name="search" class="size-5 text-muted"/>
                    <span class="sr-only">Keyword</span>
                    <input name="q" x-model="q" class="h-12 w-full bg-transparent text-[15px] outline-none placeholder:text-faint" placeholder="Job title, skill or keyword">
                    <button type="button" x-show="q" @click="q = ''; $nextTick(() => $root.closest('form').requestSubmit())" class="text-ink" aria-label="Clear search"><x-icon name="x-circle" class="size-5"/></button>
                </label>
            </div>
            <button class="btn btn-dark h-12 px-6">Find Projects</button>
        </div>
    </section>
</form>

    <div class="mt-3 grid gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-[260px_1fr]">
        {{-- Filters --}}
        <aside class="card card-pad h-fit lg:sticky lg:top-20 lg:max-h-[calc(100dvh-6rem)] lg:overflow-y-auto lg:overscroll-contain" x-data="{ open: window.innerWidth >= 1024 }">
            <div class="flex items-center justify-between">
                <button type="button" class="flex items-center gap-2 lg:pointer-events-none" @click="open = !open" :aria-expanded="open"><h2 class="text-[22px] font-bold">Filters</h2>@if($filterCount)<span class="grid size-6 place-items-center rounded-full bg-ink text-xs font-semibold text-white">{{ $filterCount }}</span>@endif<x-icon name="chevron-down" class="size-5 lg:hidden"/></button>
                <a href="{{ route('jobs.index') }}" class="text-sm font-medium text-leaf-dark hover:underline">Reset</a>
            </div>
            <div x-show="open" x-cloak class="lg:!block">
                <div class="mt-6">
                    <h3 class="font-semibold">Category</h3>
                    <select name="category" data-auto form="jobfilters" class="input mt-3">
                        <option value="">Select categories</option>
                        @foreach($categories as $c)<option value="{{ $c->slug }}" @selected(($f['category'] ?? '') === $c->slug)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <h3 class="mt-6 font-semibold">Location</h3>
                <select name="location" data-auto form="jobfilters" class="input mt-3" aria-label="Location">
                    <option value="">Anywhere</option>
                    @foreach($locations as $city => $n)<option value="{{ $city }}" @selected(($f['location'] ?? '') === $city)>{{ $city }} ({{ $n }})</option>@endforeach
                </select>
                <label class="mt-3 flex items-center gap-3 text-[15px]"><input type="checkbox" data-auto form="jobfilters" name="remote" value="1" class="checkbox" @checked($f['remote'] ?? false)> Remote OK only</label>

                <div class="my-6 h-px bg-line"></div>
                <h3 class="font-semibold">Posted within</h3>
                <div class="mt-3 grid grid-cols-4 overflow-hidden rounded-xl border border-line text-sm">
                    @foreach(['' => 'Any', '1' => '24h', '7' => '7d', '30' => '30d'] as $v => $l)
                        <label class="cursor-pointer py-2 text-center transition has-[:checked]:bg-ink has-[:checked]:font-semibold has-[:checked]:text-white {{ ! $loop->last ? 'border-r border-line' : '' }}"><input type="radio" data-auto form="jobfilters" name="posted" value="{{ $v }}" class="sr-only" @checked((string) ($f['posted'] ?? '') === (string) $v)>{{ $l }}</label>
                    @endforeach
                </div>
                <div class="mt-4 space-y-2.5">
                    <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" data-auto form="jobfilters" name="verified" value="1" class="checkbox" @checked($f['verified'] ?? false)> Verified clients only</label>
                    <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" data-auto form="jobfilters" name="featured" value="1" class="checkbox" @checked($f['featured'] ?? false)> Featured jobs only</label>
                </div>

                <div class="my-6 h-px bg-line"></div>
                <h3 class="font-semibold">Experience Level</h3>
                <div class="mt-3 space-y-2.5">
                    @foreach(['entry' => 'Entry Level', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $val => $label)
                        <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" data-auto form="jobfilters" name="level[]" value="{{ $val }}" class="checkbox" @checked(in_array($val, $f['level'] ?? []))><span class="flex-1">{{ $label }}</span><span class="rounded-md bg-zinc-100 px-1.5 text-[11px] text-muted">{{ $levelCounts[$val] ?? 0 }}</span></label>
                    @endforeach
                </div>
                <div class="my-6 h-px bg-line"></div>
                <h3 class="font-semibold">Project Type</h3>
                <div class="mt-3 grid grid-cols-2 overflow-hidden rounded-xl border border-line text-sm">
                    @foreach(['hourly' => 'Hourly', 'fixed' => 'Fixed Price'] as $val => $label)
                        <label class="cursor-pointer py-2.5 text-center transition has-[:checked]:bg-ink has-[:checked]:text-white {{ $loop->first ? 'border-r border-line' : '' }}">
                            <input type="radio" data-auto form="jobfilters" name="type" value="{{ $val }}" class="sr-only" @checked(($f['type'] ?? '') === $val)>{{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-4 space-y-2.5">
                    @foreach($budgetRanges as $key => $r)
                        <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" data-auto form="jobfilters" name="budget[]" value="{{ $key }}" class="checkbox" @checked(in_array($key, $f['budget'] ?? []))><span class="flex-1">{{ $r[0] }} <span class="text-xs text-faint">ETB</span></span><span class="rounded-md bg-zinc-100 px-1.5 text-[11px] text-muted">{{ $budgetCounts[$key] }}</span></label>
                    @endforeach
                </div>
                <div class="my-6 h-px bg-line"></div>
                <h3 class="font-semibold">Number of Proposals</h3>
                <div class="mt-3 space-y-2.5">
                    @foreach($proposalRanges as $key => $r)
                        <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" data-auto form="jobfilters" name="proposals[]" value="{{ $key }}" class="checkbox" @checked(in_array($key, $f['proposals'] ?? []))><span class="flex-1">{{ $r[0] }}</span></label>
                    @endforeach
                </div>
                <noscript><button form="jobfilters" class="btn btn-dark mt-6 w-full">Apply filters</button></noscript>
            </div>
        </aside>

        {{-- Results --}}
        <section class="min-w-0 space-y-3 sm:space-y-4">
            <div class="card card-pad flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold">Showing Results <span class="text-base font-normal text-faint">({{ number_format($jobs->total()) }})</span></h2>
                    <label class="mt-3 inline-flex items-center gap-1 text-[15px]"><span class="text-faint">Sort by:</span>
                        <select name="sort" data-auto form="jobfilters" class="cursor-pointer appearance-none bg-transparent pr-1 font-semibold outline-none">
                            @foreach(['newest' => 'Newest', 'oldest' => 'Oldest', 'budget_high' => 'Highest budget', 'budget_low' => 'Lowest budget', 'deadline' => 'Closing soon'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'newest') === $v)>{{ $l }}</option>@endforeach
                        </select><x-icon name="chevron-down" class="size-4"/>
                    </label>
                </div>
                @php $view = $f['view'] ?? 'grid'; @endphp
                <div class="flex rounded-xl border border-line bg-zinc-50 p-1">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="grid size-10 place-items-center rounded-lg {{ $view === 'list' ? 'bg-white shadow-sm ring-1 ring-line' : 'text-muted' }}" aria-label="List view"><x-icon name="list"/></a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="grid size-10 place-items-center rounded-lg {{ $view === 'grid' ? 'bg-white shadow-sm ring-1 ring-line' : 'text-muted' }}" aria-label="Grid view"><x-icon name="grid"/></a>
                </div>
            </div>

            @if($jobs->isEmpty())
                <x-empty icon="search" title="No projects match those filters" text="Try removing a filter or searching a broader keyword."><a href="{{ route('jobs.index') }}" class="btn btn-dark">Clear filters</a></x-empty>
            @else
                <div class="grid gap-3 sm:gap-4 {{ $view === 'grid' ? 'xl:grid-cols-2 3xl:grid-cols-3' : '' }}">
                    @foreach($jobs as $job)
                        <x-job-card :job="$job" :saved="in_array($job->id, $savedIds)" :applied="in_array($job->id, $appliedIds)"/>
                    @endforeach
                </div>
                @if($jobs->hasPages())<div class="card card-pad">{{ $jobs->links() }}</div>@endif
            @endif
        </section>
    </div>
</div>
</x-shell>
