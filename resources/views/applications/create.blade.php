@php
    $reqCount = $job->questions->where('is_required', true)->count();
    $typeLabel = \App\Models\ScreeningQuestion::TYPES;
    $tile = 'flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 text-sm transition hover:border-zinc-300 has-[:checked]:border-ink has-[:checked]:bg-zinc-50 has-[:checked]:ring-1 has-[:checked]:ring-ink';
@endphp
<x-layouts.app title="Apply · {{ $job->title }}">
<form method="POST" action="{{ route('applications.store', $job) }}" enctype="multipart/form-data"
      x-data="{
          cover: @js(old('cover_message', '')),
          amount: @js(old('proposed_amount', '')),
          days: @js(old('estimated_days', '')),
          availability: @js(old('availability', '')),
          picked: {{ count(old('portfolio_ids', [])) }},
          answered: 0,
          reqTotal: {{ $reqCount }},
          scan() {
              this.answered = [...this.$el.querySelectorAll('[data-required]')].filter(q =>
                  [...q.querySelectorAll('input, textarea')].some(i => (i.type === 'radio' || i.type === 'checkbox') ? i.checked : (i.type === 'file' ? i.files.length : i.value.trim() !== ''))
              ).length;
              this.picked = this.$el.querySelectorAll('input[name=\'portfolio_ids[]\']:checked').length;
          },
          get coverOk() { return this.cover.trim().length >= 30 },
          get termsOk() { return Number(this.amount) > 0 && Number(this.days) > 0 },
          get questionsOk() { return this.answered >= this.reqTotal },
          get ready() { return this.coverOk && this.termsOk && this.questionsOk },
      }"
      x-init="scan()" @input="scan()" @change="scan()"
      class="space-y-3 sm:space-y-4">@csrf

    {{-- Header --}}
    <section class="card overflow-hidden">
        <div class="bg-gradient-to-br from-zinc-50 via-white to-violet-50/60 px-5 py-6 sm:px-8 sm:py-8">
            <a href="{{ route('jobs.show', $job) }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> Back to job</a>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-muted">Your proposal for</p>
            <h1 class="mt-2 max-w-4xl font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">{{ $job->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2">
                <span class="flex items-center gap-2 text-sm font-medium"><x-avatar :user="$job->client->user" size="size-7" class="text-[10px]"/> {{ $job->client->displayName() }} @if($job->client->is_verified)<x-icon name="verified" class="size-4 text-brand"/>@endif</span>
                <span class="meta"><x-icon name="dollar" class="size-4"/> {{ $job->budgetLabel() }}</span>
                @if($job->deadline)<span class="meta"><x-icon name="calendar" class="size-4"/> Deliver by {{ $job->deadline->format('M j, Y') }}</span>@endif
                @if($job->application_deadline)<span class="meta"><x-icon name="send" class="size-4"/> Apply by {{ $job->application_deadline->format('M j, Y') }}</span>@endif
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-3 sm:gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-3 sm:space-y-4">

            {{-- 1. Cover message --}}
            <section class="card card-pad sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold transition" :class="coverOk ? 'bg-leaf text-white' : 'bg-zinc-100 text-ink-soft'"><span x-show="!coverOk">1</span><x-icon name="check" class="size-4" x-show="coverOk" x-cloak/></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-display text-xl font-bold tracking-tight">Introduce yourself</h2>
                        <p class="mt-1 text-sm text-muted">Why you're a fit, how you'd approach this brief, and the most relevant thing you've done.</p>
                        <div class="mt-5">
                            <label class="sr-only" for="cover_message">Cover message</label>
                            <textarea id="cover_message" name="cover_message" rows="8" x-model="cover" maxlength="5000" class="input h-auto py-3 text-[15px] leading-relaxed" required minlength="30" placeholder="Hi {{ \Illuminate\Support\Str::before($job->client->displayName(), ' ') }}, I'd love to help with this. Here's how I'd approach it…">{{ old('cover_message') }}</textarea>
                            <div class="mt-1.5 flex items-center justify-between text-xs">
                                <x-field-error name="cover_message"/>
                                <span class="ml-auto tabular-nums" :class="coverOk ? 'text-muted' : 'text-amber-600'"><span x-text="cover.trim().length"></span> / 5000 <span x-show="!coverOk">· at least 30</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. Price & timeline --}}
            <section class="card card-pad sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold transition" :class="termsOk ? 'bg-leaf text-white' : 'bg-zinc-100 text-ink-soft'"><span x-show="!termsOk">2</span><x-icon name="check" class="size-4" x-show="termsOk" x-cloak/></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-display text-xl font-bold tracking-tight">Price &amp; timeline</h2>
                        <p class="mt-1 text-sm text-muted">The client's budget is <span class="font-medium text-ink">{{ $job->budgetLabel() }}</span>{{ $job->budget_type === 'hourly' ? ' per hour' : '' }}.</p>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="proposed_amount">Your {{ $job->budget_type === 'hourly' ? 'hourly rate' : 'price' }}</label>
                                <div class="flex h-14 items-center rounded-xl border border-line bg-white focus-within:border-ink">
                                    <input id="proposed_amount" type="number" step="0.01" min="1" name="proposed_amount" x-model="amount" value="{{ old('proposed_amount') }}" class="h-full min-w-0 flex-1 rounded-l-xl border-0 bg-transparent px-4 font-display text-xl font-bold focus:outline-none focus:ring-0" placeholder="0" required>
                                    <select name="currency" class="h-full rounded-r-xl border-0 border-l border-line bg-zinc-50 px-3 text-sm font-medium focus:outline-none focus:ring-0" aria-label="Currency">@foreach(config('marketplace.currencies') as $c)<option @selected(old('currency', $job->currency) === $c)>{{ $c }}</option>@endforeach</select>
                                </div>
                                <x-field-error name="proposed_amount"/>
                            </div>
                            <div>
                                <label class="label" for="estimated_days">Delivery time</label>
                                <div class="flex h-14 items-center rounded-xl border border-line bg-white focus-within:border-ink">
                                    <input id="estimated_days" type="number" min="1" max="730" name="estimated_days" x-model="days" value="{{ old('estimated_days') }}" class="h-full min-w-0 flex-1 rounded-l-xl border-0 bg-transparent px-4 font-display text-xl font-bold focus:outline-none focus:ring-0" placeholder="0" required>
                                    <span class="pr-4 text-sm font-medium text-muted">days</span>
                                </div>
                                <x-field-error name="estimated_days"/>
                            </div>
                        </div>

                        <div class="mt-5">
                            <label class="label" for="availability">When can you start? <span class="font-normal text-muted">(optional)</span></label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Immediately', 'Within a few days', 'Next week', 'Next month'] as $opt)
                                    <button type="button" @click="availability = @js($opt)" class="rounded-full border px-3.5 py-1.5 text-[13px] font-medium transition" :class="availability === @js($opt) ? 'border-ink bg-ink text-white' : 'border-line bg-white text-ink-soft hover:border-zinc-300'">{{ $opt }}</button>
                                @endforeach
                            </div>
                            <input id="availability" name="availability" x-model="availability" value="{{ old('availability') }}" maxlength="120" class="input mt-2.5" placeholder="Or write your own, e.g. “From Monday, 4 hours a day”">
                        </div>
                    </div>
                </div>
            </section>

            {{-- 3. Screening questions --}}
            @if($job->questions->isNotEmpty())
            <section class="card card-pad sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold transition" :class="questionsOk ? 'bg-leaf text-white' : 'bg-zinc-100 text-ink-soft'"><span x-show="!questionsOk">3</span><x-icon name="check" class="size-4" x-show="questionsOk" x-cloak/></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="font-display text-xl font-bold tracking-tight">Screening questions</h2>
                            @if($reqCount)<span class="text-sm tabular-nums text-muted"><span x-text="answered"></span> of {{ $reqCount }} required answered</span>@endif
                        </div>
                        <p class="mt-1 text-sm text-muted">{{ $job->client->displayName() }} uses these to compare proposals side by side.</p>

                        <div class="mt-6 space-y-6">
                        @foreach($job->questions as $q)
                            @php $name = "answers[{$q->id}]"; $old = old("answers.{$q->id}"); @endphp
                            <div class="rounded-2xl border border-line p-4 sm:p-5" @if($q->is_required) data-required @endif>
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-[15px] font-semibold leading-snug"><span class="mr-1.5 text-muted">{{ $loop->iteration }}.</span>{{ $q->prompt }}</p>
                                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $q->is_required ? 'bg-rose-50 text-rose-600' : 'bg-zinc-100 text-muted' }}">{{ $q->is_required ? 'Required' : 'Optional' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-muted">{{ $typeLabel[$q->type] ?? 'Answer' }}</p>
                                <div class="mt-4">
                                @switch($q->type)
                                    @case('multiple_choice')
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">@foreach($q->options ?? [] as $o)<label class="{{ $tile }}"><input type="radio" name="{{ $name }}" value="{{ $o }}" class="accent-ink" @checked($old === $o) @required($q->is_required)> {{ $o }}</label>@endforeach</div>
                                        @break
                                    @case('multi_select')
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">@foreach($q->options ?? [] as $o)<label class="{{ $tile }}"><input type="checkbox" class="checkbox" name="{{ $name }}[]" value="{{ $o }}" @checked(in_array($o, (array) $old))> {{ $o }}</label>@endforeach</div>
                                        <p class="hint">Pick all that apply.</p>
                                        @break
                                    @case('yes_no')
                                        <div class="grid max-w-sm grid-cols-2 gap-2">@foreach(['Yes', 'No'] as $o)<label class="{{ $tile }} justify-center font-medium"><input type="radio" name="{{ $name }}" value="{{ $o }}" class="sr-only" @checked($old === $o) @required($q->is_required)><x-icon :name="$o === 'Yes' ? 'check' : 'x'" class="size-4"/> {{ $o }}</label>@endforeach</div>
                                        @break
                                    @case('number')
                                        <input type="number" step="any" min="0" name="{{ $name }}" value="{{ $old }}" class="input h-12 max-w-[200px] text-base" placeholder="0" @required($q->is_required)>
                                        @break
                                    @case('portfolio_link')
                                        <div class="relative"><x-icon name="link" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted"/><input type="url" name="{{ $name }}" value="{{ $old }}" class="input h-12 pl-10" placeholder="https://" @required($q->is_required)></div>
                                        @break
                                    @case('file')
                                        <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line bg-zinc-50/60 px-4 py-7 text-center transition hover:border-zinc-300" x-data="{ f: '' }">
                                            <x-icon name="upload" class="size-6 text-muted"/>
                                            <span class="text-sm font-medium" x-text="f || 'Choose a file'">Choose a file</span>
                                            <span class="text-xs text-muted">PDF, image, ZIP or DOCX up to 10 MB · shared only with this client</span>
                                            <input type="file" name="{{ $name }}" class="sr-only" @change="f = $event.target.files[0]?.name || ''" @required($q->is_required)>
                                        </label>
                                        @break
                                    @default
                                        <textarea name="{{ $name }}" rows="4" maxlength="3000" class="input h-auto py-3 leading-relaxed" placeholder="Your answer" @required($q->is_required)>{{ $old }}</textarea>
                                @endswitch
                                </div>
                                <x-field-error :name="'answers.'.$q->id"/>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
            </section>
            @endif

            {{-- 4. Portfolio --}}
            <section class="card card-pad sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold transition" :class="picked ? 'bg-leaf text-white' : 'bg-zinc-100 text-ink-soft'"><span x-show="!picked">{{ $job->questions->isNotEmpty() ? 4 : 3 }}</span><x-icon name="check" class="size-4" x-show="picked" x-cloak/></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="font-display text-xl font-bold tracking-tight">Show your best work</h2>
                            <span class="text-sm tabular-nums text-muted"><span x-text="picked"></span> / 6 selected</span>
                        </div>
                        <p class="mt-1 text-sm text-muted">Attach published projects from your portfolio. Pick the ones closest to this brief.</p>
                        @if($portfolio->isNotEmpty())
                            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-3">
                                @foreach($portfolio as $p)
                                    <label class="group relative block cursor-pointer" :class="picked >= 6 && ! $el.querySelector('input').checked && 'pointer-events-none opacity-40'">
                                        <input type="checkbox" name="portfolio_ids[]" value="{{ $p->id }}" class="peer sr-only" @checked(in_array($p->id, old('portfolio_ids', [])))>
                                        <span class="block aspect-[4/3] overflow-hidden rounded-xl bg-zinc-100 ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-ink peer-focus-visible:ring-brand">
                                            @if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover transition duration-300 group-hover:scale-[1.03]">@endif
                                        </span>
                                        <span class="absolute right-2 top-2 grid size-7 place-items-center rounded-full border-2 border-white bg-black/30 text-white opacity-0 shadow transition group-hover:opacity-100 peer-checked:border-ink peer-checked:bg-ink peer-checked:opacity-100"><x-icon name="check" class="size-4"/></span>
                                        <span class="mt-2 block truncate text-sm font-medium">{{ $p->title }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-5 flex flex-col items-center rounded-2xl border-2 border-dashed border-line px-6 py-10 text-center">
                                <x-icon name="layers" class="size-7 text-muted"/>
                                <p class="mt-3 font-medium">No published projects yet</p>
                                <p class="mt-1 max-w-sm text-sm text-muted">Proposals with work samples get far more replies. You can still apply without one.</p>
                                <a href="{{ route('portfolio.create') }}" class="btn btn-light btn-pill mt-4"><x-icon name="plus" class="size-4"/> Add a project</a>
                            </div>
                        @endif
                        <x-field-error name="portfolio_ids"/>
                    </div>
                </div>
            </section>
        </div>

        {{-- Summary / submit --}}
        <aside class="space-y-3 sm:space-y-4 xl:sticky xl:top-20">
            <section class="card card-pad">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Proposal summary</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @php $rows = [['coverOk', 'Cover message'], ['termsOk', 'Price & delivery']]; if ($job->questions->isNotEmpty()) $rows[] = ['questionsOk', 'Screening answers']; @endphp
                    @foreach($rows as [$flag, $label])
                        <li class="flex items-center gap-3">
                            <span class="grid size-5 place-items-center rounded-full transition" :class="{{ $flag }} ? 'bg-leaf text-white' : 'border border-line'"><x-icon name="check" class="size-3" x-show="{{ $flag }}" x-cloak/></span>
                            <span :class="{{ $flag }} ? 'text-ink' : 'text-ink-soft'">{{ $label }}</span>
                        </li>
                    @endforeach
                    <li class="flex items-center gap-3">
                        <span class="grid size-5 place-items-center rounded-full transition" :class="picked ? 'bg-leaf text-white' : 'border border-dashed border-line'"><x-icon name="check" class="size-3" x-show="picked" x-cloak/></span>
                        <span class="text-ink-soft">Portfolio projects <span class="text-muted">(recommended)</span></span>
                    </li>
                </ul>

                <div class="mt-5 rounded-xl bg-zinc-50 p-4" x-show="termsOk" x-cloak>
                    <p class="text-xs text-muted">You're proposing</p>
                    <p class="mt-0.5 font-display text-2xl font-bold"><span x-text="Number(amount).toLocaleString()"></span> <span class="text-base font-semibold text-muted">{{ $job->currency }}</span></p>
                    <p class="text-sm text-ink-soft">in <span x-text="days"></span> <span x-text="Number(days) === 1 ? 'day' : 'days'"></span><span x-show="availability"> · starts <span x-text="availability.toLowerCase()"></span></span></p>
                </div>

                <button class="btn btn-dark mt-5 h-12 w-full" :class="!ready && 'opacity-60'"><x-icon name="send" class="size-4"/> Submit proposal</button>
                <p class="mt-3 flex items-start gap-2 text-xs leading-relaxed text-muted"><x-icon name="lock" class="mt-0.5 size-3.5 shrink-0"/> Payment details stay private until you and the client both confirm the same agreement.</p>
            </section>
        </aside>
    </div>
</form>
</x-layouts.app>
