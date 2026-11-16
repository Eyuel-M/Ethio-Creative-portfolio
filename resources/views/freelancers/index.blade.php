<x-shell title="Find Freelancers">
<section class="card card-pad">
    <h1 class="font-display text-3xl font-bold tracking-tight sm:text-[34px]">Discover Creative Talent</h1>
    <p class="mt-2 text-[15px] text-ink-soft">Browse public portfolios. Evaluate the work first — contact details are never for sale.</p>
    <form id="talentfilters" method="GET" class="mt-6 flex flex-col gap-3 md:flex-row">
        <div class="flex flex-1 flex-col overflow-hidden rounded-xl border border-line bg-zinc-50/70 md:flex-row">
            <label class="flex flex-1 items-center gap-3 px-4"><x-icon name="search" class="size-5 text-muted"/><span class="sr-only">Search</span><input name="q" value="{{ $f['q'] ?? '' }}" class="h-12 w-full bg-transparent text-[15px] outline-none placeholder:text-faint" placeholder="Name, title or skill"></label>
            <div class="h-px bg-line md:h-auto md:w-px"></div>
            <label class="flex flex-1 items-center gap-3 px-4"><x-icon name="pin" class="size-5 text-muted"/><span class="sr-only">Location</span><input name="location" value="{{ $f['location'] ?? '' }}" class="h-12 w-full bg-transparent text-[15px] outline-none placeholder:text-faint" placeholder="City or region"></label>
        </div>
        <button class="btn btn-dark h-12 px-6">Search Talent</button>
    </form>
</section>

<div class="grid gap-3 sm:gap-4 lg:grid-cols-[260px_1fr]" x-data @change="if ($event.target.dataset.auto !== undefined) document.getElementById('talentfilters').requestSubmit()">
    <aside class="card card-pad h-fit lg:sticky lg:top-4">
        <div class="flex items-center justify-between"><h2 class="text-[22px] font-bold">Filters</h2><a href="{{ route('freelancers.index') }}" class="text-sm font-medium text-leaf-dark hover:underline">Reset</a></div>
        <h3 class="mt-6 font-semibold">Category</h3>
        <select name="category" form="talentfilters" data-auto class="input mt-3">
            <option value="">All categories</option>
            @foreach($categories as $c)<option value="{{ $c->slug }}" @selected(($f['category'] ?? '') === $c->slug)>{{ $c->name }}</option>@endforeach
        </select>
        <div class="my-6 h-px bg-line"></div>
        <h3 class="font-semibold">Experience Level</h3>
        <div class="mt-3 space-y-2.5">
            @foreach(['entry' => 'Entry Level', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)
                <label class="flex items-center gap-3 text-[15px]"><input type="checkbox" form="talentfilters" data-auto name="level[]" value="{{ $v }}" class="checkbox" @checked(in_array($v, $f['level'] ?? []))> {{ $l }}</label>
            @endforeach
        </div>
        <div class="my-6 h-px bg-line"></div>
        <h3 class="font-semibold">Availability</h3>
        <div class="mt-3 grid grid-cols-2 overflow-hidden rounded-xl border border-line text-sm">
            @foreach(['available' => 'Available', 'busy' => 'Busy'] as $v => $l)
                <label class="cursor-pointer py-2.5 text-center has-[:checked]:bg-ink has-[:checked]:text-white {{ $loop->first ? 'border-r border-line' : '' }}"><input type="radio" form="talentfilters" data-auto name="availability" value="{{ $v }}" class="sr-only" @checked(($f['availability'] ?? '') === $v)>{{ $l }}</label>
            @endforeach
        </div>
    </aside>

    <section class="min-w-0 space-y-3 sm:space-y-4">
        <div class="card card-pad flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-bold">Showing Talent <span class="text-base font-normal text-faint">({{ number_format($profiles->total()) }})</span></h2>
            <label class="inline-flex items-center gap-1 text-[15px]"><span class="text-faint">Sort by:</span>
                <select name="sort" form="talentfilters" data-auto class="cursor-pointer appearance-none bg-transparent font-semibold outline-none">
                    @foreach(['featured' => 'Recommended', 'newest' => 'Newest', 'rating' => 'Top rated'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'featured') === $v)>{{ $l }}</option>@endforeach
                </select><x-icon name="chevron-down" class="size-4"/>
            </label>
        </div>
        @if($profiles->isEmpty())
            <x-empty icon="users" title="No creatives found" text="Try a different category or keyword."/>
        @else
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 2xl:grid-cols-3">
                @foreach($profiles as $p)<x-talent-card :profile="$p" :shortlisted="in_array($p->id, $shortlisted)"/>@endforeach
            </div>
            <div class="card card-pad">{{ $profiles->links() }}</div>
        @endif
        <p class="px-1 text-xs text-muted">“Sponsored” profiles paid for promotional placement. Promotion is not a rating and does not guarantee quality.</p>
    </section>
</div>
</x-shell>
