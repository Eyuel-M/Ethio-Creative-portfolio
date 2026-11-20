<x-shell title="Find Freelancers">
<section class="card card-pad">
    <h1 class="font-display text-3xl font-bold tracking-tight sm:text-[34px]">Discover Creative Talent</h1>
    <p class="mt-2 text-[15px] text-ink-soft">Browse public portfolios. Evaluate the work first — contact details are never for sale.</p>
    <form id="talentfilters" method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row">
        <label class="flex h-12 flex-1 items-center gap-3 rounded-xl border border-line bg-zinc-50/70 px-4"><x-icon name="search" class="size-5 text-muted"/><span class="sr-only">Search</span><input name="q" value="{{ $f['q'] ?? '' }}" class="h-full w-full bg-transparent text-[15px] outline-none placeholder:text-faint" placeholder="Name, title or skill"></label>
        <button class="btn btn-dark h-12 px-6">Search Talent</button>
    </form>
</section>

@php
    $seg = 'cursor-pointer py-2 text-center text-sm transition has-[:checked]:bg-ink has-[:checked]:font-semibold has-[:checked]:text-white';
@endphp
<div class="grid gap-3 sm:gap-4 lg:grid-cols-[280px_1fr]" x-data @change="if ($event.target.dataset.auto !== undefined) document.getElementById('talentfilters').requestSubmit()">
    <aside class="card card-pad h-fit lg:sticky lg:top-20" x-data="{ open: window.innerWidth >= 1024 }">
        <div class="flex items-center justify-between">
            <button type="button" class="flex items-center gap-2 lg:pointer-events-none" @click="open = !open" :aria-expanded="open">
                <h2 class="text-[22px] font-bold">Filters</h2>
                @if($filterCount)<span class="grid size-6 place-items-center rounded-full bg-ink text-xs font-semibold text-white">{{ $filterCount }}</span>@endif
                <x-icon name="chevron-down" class="size-5 lg:hidden"/>
            </button>
            <a href="{{ route('freelancers.index', array_filter(['q' => $f['q'] ?? null])) }}" class="text-sm font-medium text-leaf-dark hover:underline">Reset</a>
        </div>
        <div x-show="open" x-cloak class="lg:!block">
            <h3 class="mt-6 font-semibold">Category</h3>
            <select name="category" form="talentfilters" data-auto class="input mt-3" aria-label="Category">
                <option value="">All categories</option>
                @foreach($categories as $c)<option value="{{ $c->slug }}" @selected(($f['category'] ?? '') === $c->slug)>{{ $c->name }}</option>@endforeach
            </select>

            <h3 class="mt-6 font-semibold">Location</h3>
            <select name="location" form="talentfilters" data-auto class="input mt-3" aria-label="Location">
                <option value="">Anywhere</option>
                @foreach($locations as $city => $n)<option value="{{ $city }}" @selected(($f['location'] ?? '') === $city)>{{ $city }} ({{ $n }})</option>@endforeach
            </select>

            <div class="my-6 h-px bg-line"></div>
            <h3 class="font-semibold">Availability</h3>
            <div class="mt-3 grid grid-cols-3 overflow-hidden rounded-xl border border-line">
                @foreach(['' => 'Any', 'available' => 'Available', 'busy' => 'Busy'] as $v => $l)
                    <label class="{{ $seg }} {{ ! $loop->last ? 'border-r border-line' : '' }}"><input type="radio" form="talentfilters" data-auto name="availability" value="{{ $v }}" class="sr-only" @checked(($f['availability'] ?? '') === $v)>{{ $l }}</label>
                @endforeach
            </div>

            <h3 class="mt-6 font-semibold">Experience level</h3>
            <div class="mt-3 space-y-2.5">
                @foreach(['entry' => 'Entry Level', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)
                    <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" form="talentfilters" data-auto name="level[]" value="{{ $v }}" class="checkbox" @checked(in_array($v, $f['level'] ?? []))> {{ $l }}</label>
                @endforeach
            </div>

            <div class="my-6 h-px bg-line"></div>
            <h3 class="font-semibold">Rating</h3>
            <div class="mt-3 grid grid-cols-3 overflow-hidden rounded-xl border border-line">
                @foreach(['' => 'Any', '4' => '4+ ★', '4.5' => '4.5+ ★'] as $v => $l)
                    <label class="{{ $seg }} {{ ! $loop->last ? 'border-r border-line' : '' }}"><input type="radio" form="talentfilters" data-auto name="rating" value="{{ $v }}" class="sr-only" @checked((string) ($f['rating'] ?? '') === (string) $v)>{{ $l }}</label>
                @endforeach
            </div>

            <h3 class="mt-6 font-semibold">Hourly rate <span class="text-sm font-normal text-muted">(ETB)</span></h3>
            <div class="mt-3 flex items-center gap-2">
                <label class="sr-only" for="rate_min">Minimum hourly rate</label>
                <input id="rate_min" type="number" min="0" step="50" name="rate_min" form="talentfilters" data-auto value="{{ $f['rate_min'] ?? '' }}" class="input h-10" placeholder="Min">
                <span class="text-muted">–</span>
                <label class="sr-only" for="rate_max">Maximum hourly rate</label>
                <input id="rate_max" type="number" min="0" step="50" name="rate_max" form="talentfilters" data-auto value="{{ $f['rate_max'] ?? '' }}" class="input h-10" placeholder="Max">
            </div>

            <h3 class="mt-6 font-semibold">Portfolio size</h3>
            <div class="mt-3 grid grid-cols-4 overflow-hidden rounded-xl border border-line">
                @foreach(['' => 'Any', '1' => '1+', '3' => '3+', '5' => '5+'] as $v => $l)
                    <label class="{{ $seg }} {{ ! $loop->last ? 'border-r border-line' : '' }}"><input type="radio" form="talentfilters" data-auto name="projects" value="{{ $v }}" class="sr-only" @checked((string) ($f['projects'] ?? '') === (string) $v)>{{ $l }}</label>
                @endforeach
            </div>
            <label class="mt-4 flex items-center gap-3 text-[15px]"><input type="checkbox" form="talentfilters" data-auto name="experienced" value="1" class="checkbox" @checked($f['experienced'] ?? false)> Has completed projects</label>

            @if($languages->isNotEmpty())
                <div class="my-6 h-px bg-line"></div>
                <h3 class="font-semibold">Languages</h3>
                <div class="mt-3 space-y-2.5">
                    @foreach($languages as $lang => $n)
                        <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" form="talentfilters" data-auto name="languages[]" value="{{ $lang }}" class="checkbox" @checked(in_array($lang, $f['languages'] ?? []))><span class="flex-1">{{ $lang }}</span><span class="rounded-md bg-zinc-100 px-1.5 text-[11px] text-muted">{{ $n }}</span></label>
                    @endforeach
                </div>
            @endif

            @if($popularSkills->isNotEmpty())
                <div class="my-6 h-px bg-line"></div>
                <h3 class="font-semibold">Skills</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($popularSkills as $sk)
                        <label class="cursor-pointer rounded-lg border border-line bg-white px-2.5 py-1 text-xs font-medium text-ink-soft transition hover:border-zinc-300 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white">
                            <input type="checkbox" form="talentfilters" data-auto name="skills[]" value="{{ $sk->slug }}" class="sr-only" @checked(in_array($sk->slug, $f['skills'] ?? []))>{{ $sk->name }}
                        </label>
                    @endforeach
                </div>
            @endif
            <noscript><button form="talentfilters" class="btn btn-dark mt-6 w-full">Apply filters</button></noscript>
        </div>
    </aside>

    <section class="min-w-0 space-y-3 sm:space-y-4">
        <div class="card card-pad flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-bold">Showing Talent <span class="text-base font-normal text-faint">({{ number_format($profiles->total()) }})</span></h2>
            <label class="inline-flex items-center gap-1 text-[15px]"><span class="text-faint">Sort by:</span>
                <select name="sort" form="talentfilters" data-auto class="cursor-pointer appearance-none bg-transparent font-semibold outline-none">
                    @foreach(['featured' => 'Recommended', 'newest' => 'Newest', 'rating' => 'Top rated', 'projects' => 'Most work', 'rate_low' => 'Rate: low to high', 'rate_high' => 'Rate: high to low'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'featured') === $v)>{{ $l }}</option>@endforeach
                </select><x-icon name="chevron-down" class="size-4"/>
            </label>
        </div>
        @if($profiles->isEmpty())
            <x-empty icon="users" title="No creatives match these filters" text="Try removing a filter or widening the rate range."><a href="{{ route('freelancers.index') }}" class="btn btn-dark">Clear filters</a></x-empty>
        @else
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 2xl:grid-cols-3">
                @foreach($profiles as $p)<x-talent-card :profile="$p" :shortlisted="in_array($p->id, $shortlisted)"/>@endforeach
            </div>
            @if($profiles->hasPages())<div class="card card-pad">{{ $profiles->links() }}</div>@endif
        @endif
    </section>
</div>
</x-shell>
