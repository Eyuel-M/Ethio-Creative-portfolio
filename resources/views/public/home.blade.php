<x-layouts.public :hero="true">
    {{-- Hero --}}
    <section class="px-3 pt-3 sm:px-4 sm:pt-4">
        <div class="hero-gradient relative overflow-hidden flex min-h-[calc(100svh-1.5rem)] flex-col justify-center rounded-[28px] px-4 pb-10 pt-28 sm:min-h-[calc(100svh-2rem)] sm:px-6 sm:pt-32">
            <div class="pointer-events-none absolute left-1/2 top-24 size-[780px] -translate-x-1/2 rounded-full border border-white/70"></div>
            <div class="pointer-events-none absolute left-1/2 top-48 size-[540px] -translate-x-1/2 rounded-full border border-white/60"></div>

            <div class="relative mx-auto max-w-4xl text-center">
                <div class="flex justify-center -space-x-2">
                    @foreach($featuredTalent->take(4) as $t)
                        <x-avatar :user="$t->user" size="size-12" :ring="true" class="text-sm shadow-md"/>
                    @endforeach
                </div>
                <h1 class="mt-6 font-display text-[40px] font-bold leading-[1.05] tracking-[-0.035em] sm:text-6xl lg:text-[76px]">
                    Your Marketplace
                    <span class="mx-1 inline-grid size-10 -rotate-6 place-items-center rounded-xl bg-gradient-to-b from-[#6b9bff] to-brand align-middle text-white shadow-lg sm:size-14"><x-icon name="briefcase" class="size-5 sm:size-7"/></span>
                    for<br class="hidden sm:block"> Creative
                    <span class="mx-1 inline-grid size-10 place-items-center rounded-full bg-gradient-to-b from-[#c4a5ff] to-violet align-middle text-xl text-white shadow-lg sm:size-14 sm:text-3xl">&amp;</span>
                    Digital Talent
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-base text-ink-soft sm:text-lg">Ethiopian designers, illustrators, photographers and animators — discovered by their work, hired through clear agreements.</p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('register') }}" class="btn btn-dark btn-pill h-12 pl-6 pr-2">Join for free <span class="grid size-8 place-items-center rounded-full bg-white text-ink"><x-icon name="arrow-up-right" class="size-4"/></span></a>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-pill h-12 bg-white/80 px-6 text-ink backdrop-blur hover:bg-white">Hire talent</a>
                </div>
            </div>

            {{-- Search talent --}}
            <form action="{{ route('freelancers.index') }}" class="relative mx-auto mt-14 max-w-5xl rounded-3xl border border-white bg-white/75 p-5 shadow-[0_30px_60px_-30px_rgba(60,60,120,.35)] backdrop-blur-xl sm:p-6">
                <div class="flex items-center gap-2.5">
                    <span class="grid size-8 place-items-center rounded-full bg-brand text-white"><x-icon name="search" class="size-4"/></span>
                    <h2 class="font-display text-xl font-semibold">Search Talent</h2>
                </div>
                <p class="mt-1 text-sm text-muted">Browse public portfolios — no account needed to look.</p>
                <div class="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_1fr_auto]">
                    <label class="sr-only" for="hs-category">Category</label>
                    <select id="hs-category" name="category" class="input h-12 rounded-full border-transparent bg-zinc-100/80 px-5">
                        <option value="">Categories</option>
                        @foreach($categories as $c)<option value="{{ $c->slug }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <label class="sr-only" for="hs-location">Location</label>
                    <input id="hs-location" name="location" class="input h-12 rounded-full border-transparent bg-zinc-100/80 px-5" placeholder="City (e.g. Addis Ababa)">
                    <label class="sr-only" for="hs-level">Experience</label>
                    <select id="hs-level" name="level[]" class="input h-12 rounded-full border-transparent bg-zinc-100/80 px-5">
                        <option value="">Experience level</option>
                        <option value="entry">Entry</option><option value="intermediate">Intermediate</option><option value="expert">Expert</option>
                    </select>
                    <button class="btn btn-dark btn-pill h-12 px-7">Search Talent</button>
                </div>
            </form>
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-[1320px] px-4 py-20 sm:px-6">
        <div class="grid items-center gap-10 lg:grid-cols-[1.35fr_1fr]">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach([
                    ['01', 'layers', 'bg-brand', 'Portfolio-first profiles', 'Structured case studies — not just one image — that show how you think.'],
                    ['02', 'shield', 'bg-ink', 'Private until agreed', 'Contact and CBE / Telebirr details unlock only after both sides confirm the same agreement.'],
                    ['03', 'file', 'bg-ink', 'Structured hiring', 'Screening questions, side-by-side comparison and versioned agreements.'],
                ] as [$n, $icon, $bg, $title, $text])
                    <div class="flex min-h-[300px] flex-col rounded-3xl bg-zinc-100/70 p-6">
                        <span class="text-sm text-faint">/ {{ $n }}</span>
                        <span class="mt-auto grid size-14 place-items-center rounded-full {{ $bg }} text-white"><x-icon :name="$icon" class="size-6"/></span>
                        <h3 class="mt-6 font-display text-xl font-semibold leading-tight">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <div>
                <span class="eyebrow">Why us</span>
                <h2 class="mt-5 font-display text-4xl font-semibold leading-[1.1] tracking-tight sm:text-[44px]">A creative marketplace that <span class="text-brand">never touches</span> your project money.</h2>
                <p class="mt-5 text-muted">Clients pay a small fee to publish a job. Freelancers join free. When you agree on a project, the client pays you directly through CBE or Telebirr — no escrow, no commission.</p>
                <div class="mt-7 flex items-center">
                    <a href="{{ route('how') }}" class="btn btn-brand btn-pill h-12 px-7">How it works</a>
                    <a href="{{ route('how') }}" class="-ml-1 grid size-12 place-items-center rounded-full bg-ink text-white" aria-label="How it works"><x-icon name="arrow-up-right" class="size-4"/></a>
                </div>
                <dl class="mt-10 grid grid-cols-3 gap-4 border-t border-line pt-6">
                    <div><dt class="text-xs text-muted">Creatives</dt><dd class="font-display text-2xl font-bold">{{ number_format($stats['freelancers']) }}</dd></div>
                    <div><dt class="text-xs text-muted">Case studies</dt><dd class="font-display text-2xl font-bold">{{ number_format($stats['projects']) }}</dd></div>
                    <div><dt class="text-xs text-muted">Open jobs</dt><dd class="font-display text-2xl font-bold">{{ number_format($stats['jobs']) }}</dd></div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Showcase: wide bento of real portfolio work --}}
    @if($showcase->count() >= 4)
    @php
        // 7 tiles fill a 4×3 grid: big hero, tall, two smalls, small, wide, small.
        $spans = $showcase->count() >= 7
            ? ['md:col-span-2 md:row-span-2', 'md:row-span-2', '', '', '', 'md:col-span-2', '']
            : array_fill(0, 7, '');
    @endphp
    <section>
        <div class="mx-auto max-w-[1600px] px-4 pb-20 pt-4 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="eyebrow">Showcase</span>
                    <h2 class="mt-4 font-display text-4xl font-semibold tracking-tight sm:text-5xl">Fresh work from
                        <span class="mx-1 inline-grid size-10 place-items-center rounded-full bg-gradient-to-b from-[#ffb072] to-[#ff7a2f] align-middle text-white"><x-icon name="sparkles" class="size-5"/></span> our creatives</h2>
                </div>
                <a href="{{ route('explore') }}" class="btn btn-dark btn-pill h-11 px-6">Explore more <x-icon name="arrow-right" class="size-4"/></a>
            </div>
            <div class="mt-10 grid auto-rows-[220px] gap-4 sm:grid-cols-2 md:grid-cols-4 {{ $showcase->count() >= 7 ? '' : 'lg:grid-cols-3' }}">
                @foreach($showcase as $i => $p)
                    <a href="{{ route('portfolio.show', $p) }}" class="group relative overflow-hidden rounded-2xl bg-zinc-900 {{ $spans[$i] ?? '' }}">
                        <img src="{{ $p->coverUrl() }}" alt="{{ $p->cover_alt ?? $p->title }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.04]" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-black/10 transition group-hover:from-black/80"></div>
                        @if($p->category)<span class="absolute left-3 top-3 rounded-full bg-black/45 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur">{{ $p->category->name }}</span>@endif
                        <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                            <p class="font-display font-semibold leading-snug {{ $i === 0 ? 'text-2xl sm:text-3xl' : 'text-[15px]' }}">{{ $p->title }}</p>
                            <div class="mt-2 flex items-center justify-between gap-3 text-[13px] text-white/85">
                                <span class="flex min-w-0 items-center gap-2"><x-avatar :user="$p->profile->user" size="size-6" class="text-[10px]"/><span class="truncate">{{ $p->profile->user->displayName() }}</span></span>
                                <span class="flex shrink-0 items-center gap-3"><span class="flex items-center gap-1"><x-icon name="heart" class="size-3.5"/>{{ $p->likes_count }}</span><span class="flex items-center gap-1"><x-icon name="eye" class="size-3.5"/>{{ $p->views >= 1000 ? round($p->views / 1000, 1).'k' : $p->views }}</span></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-10 text-center">
                <a href="{{ route('explore') }}" class="btn btn-light btn-pill h-11 px-6">See all projects <x-icon name="arrow-right" class="size-4"/></a>
            </div>
        </div>
    </section>
    @endif

    {{-- Categories --}}
    <section class="border-y border-line bg-white">
        <div class="mx-auto max-w-[1320px] px-4 py-20 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="eyebrow">Talent</span>
                    <h2 class="mt-4 font-display text-4xl font-semibold tracking-tight sm:text-5xl">Explore Our Expert
                        <span class="mx-1 inline-grid size-10 place-items-center rounded-full bg-gradient-to-b from-[#c4a5ff] to-violet align-middle text-white"><x-icon name="star" class="size-5 fill-white"/></span> Talent</h2>
                </div>
                <a href="{{ route('freelancers.index') }}" class="btn btn-light btn-pill">View all talent <x-icon name="arrow-right" class="size-4"/></a>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($categories->take(8) as $i => $cat)
                    @php $dark = $i === 1; $people = $featuredTalent->where('category_id', $cat->id)->take(4); @endphp
                    <a href="{{ route('freelancers.index', ['category' => $cat->slug]) }}" class="group flex min-h-[260px] flex-col rounded-3xl border p-6 transition {{ $dark ? 'border-ink bg-ink text-white' : 'border-line bg-[#fafafa] hover:bg-white hover:shadow-lg' }}">
                        <span class="grid size-12 place-items-center rounded-full {{ $dark ? 'bg-white text-ink' : 'bg-zinc-200/70 text-ink' }}"><x-icon :name="$cat->icon ?? 'pen'" class="size-5"/></span>
                        <h3 class="mt-6 font-display text-2xl font-medium">{{ $cat->name }}</h3>
                        <p class="mt-auto pt-6 text-[13px] leading-relaxed {{ $dark ? 'text-white/70' : 'text-ink-soft' }}">{{ $cat->description }}</p>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex -space-x-2">
                                @forelse($people as $p)<x-avatar :user="$p->user" size="size-9" :ring="true" class="text-xs"/>@empty<span class="text-xs {{ $dark ? 'text-white/60' : 'text-muted' }}">{{ $cat->freelancers_count }} creatives</span>@endforelse
                            </div>
                            <span class="grid size-10 place-items-center rounded-full border transition group-hover:rotate-45 {{ $dark ? 'border-white/20' : 'border-line bg-white' }}"><x-icon name="arrow-up-right" class="size-4"/></span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Jobs --}}
    @if($jobs->isNotEmpty())
    <section class="mx-auto max-w-[1320px] px-4 py-20 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><span class="eyebrow">Opportunities</span><h2 class="mt-4 font-display text-4xl font-semibold tracking-tight">Fresh creative jobs</h2></div>
            <a href="{{ route('jobs.index') }}" class="btn btn-light btn-pill">Browse all jobs <x-icon name="arrow-right" class="size-4"/></a>
        </div>
        <div class="mt-8 grid gap-4 lg:grid-cols-2">
            @foreach($jobs as $job)<x-job-card :job="$job"/>@endforeach
        </div>
    </section>
    @endif

    {{-- CTA --}}
    <section class="mx-auto max-w-[1320px] px-4 sm:px-6">
        <div class="relative overflow-hidden rounded-[28px] bg-ink px-6 py-16 text-center text-white sm:px-12">
            <div class="grid-lines absolute inset-0 opacity-[.15] invert"></div>
            <div class="relative">
                <h2 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl">Show your work. Get hired. Get paid directly.</h2>
                <p class="mx-auto mt-4 max-w-xl text-white/70">Your first {{ config('marketplace.portfolio_limits.free') }} case studies are free, forever.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register', ['role' => 'freelancer']) }}" class="btn btn-pill h-12 bg-white px-7 text-ink hover:bg-zinc-100">Create your portfolio</a>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-pill h-12 border border-white/25 px-7 hover:bg-white/10">Post a job</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
