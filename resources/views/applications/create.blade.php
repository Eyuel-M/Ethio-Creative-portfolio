<x-layouts.app title="Apply">
<form method="POST" action="{{ route('applications.store', $job) }}" enctype="multipart/form-data" class="grid gap-3 sm:gap-4 xl:grid-cols-[1fr_340px]">@csrf
    <div class="space-y-3 sm:space-y-4">
        <section class="card card-pad">
            <a href="{{ route('jobs.show', $job) }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> Back to job</a>
            <h1 class="mt-3 font-display text-3xl font-bold">Apply: {{ $job->title }}</h1>
            <p class="mt-1 text-muted">{{ $job->client->displayName() }} · {{ $job->budgetLabel() }}</p>
        </section>

        <section class="card card-pad space-y-4">
            <h2 class="panel-title">Your proposal</h2>
            <div><label class="label" for="cover_message">Cover message</label><textarea id="cover_message" name="cover_message" rows="6" class="input" required minlength="30" placeholder="Why you're a good fit, how you'd approach it, relevant experience…">{{ old('cover_message') }}</textarea><x-field-error name="cover_message"/></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="label" for="proposed_amount">Proposed price</label><div class="flex"><input id="proposed_amount" type="number" step="0.01" min="1" name="proposed_amount" value="{{ old('proposed_amount') }}" class="input rounded-r-none" required><select name="currency" class="input w-24 rounded-l-none border-l-0" aria-label="Currency">@foreach(config('marketplace.currencies') as $c)<option @selected(old('currency', $job->currency) === $c)>{{ $c }}</option>@endforeach</select></div><x-field-error name="proposed_amount"/></div>
                <div><label class="label" for="estimated_days">Delivery (days)</label><input id="estimated_days" type="number" min="1" name="estimated_days" value="{{ old('estimated_days') }}" class="input" required><x-field-error name="estimated_days"/></div>
                <div><label class="label" for="availability">Availability</label><input id="availability" name="availability" value="{{ old('availability') }}" class="input" placeholder="Can start Monday"></div>
            </div>
            <p class="flex gap-2 rounded-xl bg-zinc-50 p-3 text-xs text-muted"><x-icon name="bank" class="size-4 shrink-0"/> Your price is a proposal for the client. If you agree, the client pays you directly — the platform never collects or takes a cut.</p>
        </section>

        @if($job->questions->isNotEmpty())
        <section class="card card-pad space-y-5">
            <h2 class="panel-title">Screening questions</h2>
            @foreach($job->questions as $q)
                @php $name = "answers[{$q->id}]"; $old = old("answers.{$q->id}"); @endphp
                <div>
                    <p class="label">{{ $loop->iteration }}. {{ $q->prompt }} @if($q->is_required)<span class="text-rose-500">*</span>@else<span class="font-normal text-muted">(optional)</span>@endif</p>
                    @switch($q->type)
                        @case('multiple_choice')
                            <div class="grid gap-2 sm:grid-cols-2">@foreach($q->options ?? [] as $o)<label class="flex items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-sm has-[:checked]:border-ink has-[:checked]:bg-zinc-50"><input type="radio" name="{{ $name }}" value="{{ $o }}" @checked($old === $o) @required($q->is_required)> {{ $o }}</label>@endforeach</div>
                            @break
                        @case('multi_select')
                            <div class="grid gap-2 sm:grid-cols-2">@foreach($q->options ?? [] as $o)<label class="flex items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-sm has-[:checked]:border-ink has-[:checked]:bg-zinc-50"><input type="checkbox" class="checkbox" name="{{ $name }}[]" value="{{ $o }}" @checked(in_array($o, (array) $old))> {{ $o }}</label>@endforeach</div>
                            @break
                        @case('yes_no')
                            <div class="flex gap-2">@foreach(['Yes', 'No'] as $o)<label class="flex items-center gap-2 rounded-xl border border-line px-5 py-2.5 text-sm has-[:checked]:border-ink has-[:checked]:bg-zinc-50"><input type="radio" name="{{ $name }}" value="{{ $o }}" @checked($old === $o) @required($q->is_required)> {{ $o }}</label>@endforeach</div>
                            @break
                        @case('number')
                            <input type="number" step="any" min="0" name="{{ $name }}" value="{{ $old }}" class="input max-w-xs" @required($q->is_required)>
                            @break
                        @case('portfolio_link')
                            <input type="url" name="{{ $name }}" value="{{ $old }}" class="input" placeholder="https://" @required($q->is_required)>
                            @break
                        @case('file')
                            <input type="file" name="{{ $name }}" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium" @required($q->is_required)><p class="hint">PDF, image, ZIP or DOCX up to 10 MB. Shared only with this client.</p>
                            @break
                        @default
                            <textarea name="{{ $name }}" rows="3" class="input" @required($q->is_required)>{{ $old }}</textarea>
                    @endswitch
                    <x-field-error :name="'answers.'.$q->id"/>
                </div>
            @endforeach
        </section>
        @endif
    </div>

    <aside class="space-y-3 sm:space-y-4 xl:sticky xl:top-4 xl:h-fit">
        <section class="card card-pad">
            <h2 class="font-semibold">Attach portfolio projects</h2>
            <p class="mt-1 text-sm text-muted">Pick up to 6 published case studies. No re-uploading.</p>
            <div class="mt-4 space-y-2">
                @forelse($portfolio as $p)
                    <label class="flex items-center gap-3 rounded-xl border border-line p-2 has-[:checked]:border-ink has-[:checked]:bg-zinc-50">
                        <input type="checkbox" name="portfolio_ids[]" value="{{ $p->id }}" class="checkbox ml-1" @checked(in_array($p->id, old('portfolio_ids', [])))>
                        <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover">@endif</span>
                        <span class="text-sm font-medium leading-snug">{{ $p->title }}</span>
                    </label>
                @empty
                    <p class="rounded-xl bg-zinc-50 p-3 text-sm text-muted">No published projects yet. <a href="{{ route('portfolio.create') }}" class="font-medium underline">Add one</a></p>
                @endforelse
            </div>
        </section>
        <section class="card card-pad">
            <button class="btn btn-dark h-12 w-full">Submit application</button>
            <p class="mt-3 text-xs text-muted">Your phone, email and CBE/Telebirr details are <span class="font-semibold text-ink">not</span> shared by applying.</p>
        </section>
    </aside>
</form>
</x-layouts.app>
