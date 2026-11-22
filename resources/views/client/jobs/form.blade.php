@php $locked = $job->exists && $job->applications()->exists(); @endphp
<x-layouts.app :title="$job->exists ? 'Edit job' : 'Post a job'">
<form method="POST" action="{{ $job->exists ? route('client.jobs.update', $job) : route('client.jobs.store') }}" class="grid gap-3 sm:gap-4 xl:grid-cols-[1fr_340px]"
      x-data="jobBuilder(@js(old('questions', $questions)), @js($templates), @js((string) old('category_id', $job->category_id)))">
    @csrf @if($job->exists) @method('PUT') @endif
    <div class="space-y-3 sm:space-y-4">
        <section class="card card-pad">
            <a href="{{ route('client.jobs.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> My jobs</a>
            <h1 class="mt-3 font-display text-3xl font-bold">{{ $job->exists ? 'Edit job' : 'Post a creative job' }}</h1>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label" for="title">Job title</label><input id="title" name="title" value="{{ old('title', $job->title) }}" class="input" required placeholder="e.g. Brand identity for a coffee roastery"><x-field-error name="title"/></div>
                <div><label class="label" for="category_id">Creative category</label><select id="category_id" name="category_id" class="input" required x-model="category"><option value="">Select…</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select><x-field-error name="category_id"/></div>
                <div><label class="label" for="experience_level">Experience level</label><select id="experience_level" name="experience_level" class="input">@foreach(['entry' => 'Entry', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)<option value="{{ $v }}" @selected(old('experience_level', $job->experience_level) === $v)>{{ $l }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label class="label" for="description">Description</label><textarea id="description" name="description" rows="6" class="input" required minlength="40" placeholder="Background, goals, audience, style references…">{{ old('description', $job->description) }}</textarea><x-field-error name="description"/></div>
                <div class="sm:col-span-2"><label class="label" for="deliverables">Expected deliverables</label><textarea id="deliverables" name="deliverables" rows="3" class="input" placeholder="Logo files (AI, SVG, PNG), brand guide PDF…">{{ old('deliverables', $job->deliverables) }}</textarea></div>
                <div class="sm:col-span-2"><label class="label" for="skills">Required skills / software</label><input id="skills" name="skills" list="skill-list" value="{{ old('skills', $skillNames) }}" class="input" placeholder="Illustrator, Branding, Typography"><datalist id="skill-list">@foreach($allSkills as $s)<option value="{{ $s }}">@endforeach</datalist><p class="hint">Comma separated</p></div>
            </div>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Budget &amp; timeline</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2" x-data="{ type: '{{ old('budget_type', $job->budget_type) }}' }">
                <div class="sm:col-span-2 grid grid-cols-2 overflow-hidden rounded-xl border border-line text-sm sm:w-80">
                    @foreach(['fixed' => 'Fixed Price', 'hourly' => 'Hourly'] as $v => $l)<label class="cursor-pointer py-2.5 text-center has-[:checked]:bg-ink has-[:checked]:text-white {{ $loop->first ? 'border-r border-line' : '' }}"><input type="radio" name="budget_type" value="{{ $v }}" x-model="type" class="sr-only">{{ $l }}</label>@endforeach
                </div>
                <div><label class="label" for="budget_min">Budget from</label><input id="budget_min" type="number" step="0.01" min="0" name="budget_min" value="{{ old('budget_min', \App\Support\Money::toMajor($job->budget_min_minor)) }}" class="input"></div>
                <div><label class="label" for="budget_max">Budget to</label><div class="flex"><input id="budget_max" type="number" step="0.01" min="0" name="budget_max" value="{{ old('budget_max', \App\Support\Money::toMajor($job->budget_max_minor)) }}" class="input rounded-r-none"><select name="currency" class="input w-24 rounded-l-none border-l-0" aria-label="Currency">@foreach(config('marketplace.currencies') as $c)<option @selected(old('currency', $job->currency) === $c)>{{ $c }}</option>@endforeach</select></div><x-field-error name="budget_max"/></div>
                <div><label class="label" for="duration">Duration</label><input id="duration" name="duration" value="{{ old('duration', $job->duration) }}" class="input" placeholder="2–3 weeks"></div>
                <div><label class="label" for="deadline">Delivery deadline</label><input id="deadline" type="date" name="deadline" value="{{ old('deadline', $job->deadline?->format('Y-m-d')) }}" class="input"></div>
                <div><label class="label" for="application_deadline">Application deadline</label><input id="application_deadline" type="date" name="application_deadline" value="{{ old('application_deadline', $job->application_deadline?->format('Y-m-d')) }}" class="input"></div>
                <div><label class="label" for="hires_needed">Number of hires</label><input id="hires_needed" type="number" min="1" max="20" name="hires_needed" value="{{ old('hires_needed', $job->hires_needed) }}" class="input"></div>
                <div><label class="label" for="location">Location</label><input id="location" name="location" value="{{ old('location', $job->location) }}" class="input" placeholder="Addis Ababa"></div>
                <label class="flex items-center gap-2 self-end pb-3 text-sm"><input type="hidden" name="is_remote" value="0"><input type="checkbox" name="is_remote" value="1" class="checkbox" @checked(old('is_remote', $job->is_remote))> Remote work OK</label>
            </div>
        </section>

        <section class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="panel-title">Screening questions</h2><p class="text-sm text-muted">Every applicant answers these, so you can compare like-for-like.</p></div>
                @unless($locked)
                <div class="flex gap-2">
                    <button type="button" class="btn btn-sm btn-light" @click="useTemplate()" :disabled="!category"><x-icon name="sparkles" class="size-4"/> Use category template</button>
                    <button type="button" class="btn btn-sm btn-dark" @click="add()"><x-icon name="plus" class="size-4"/> Add question</button>
                </div>
                @endunless
            </div>
            @if($locked)
                <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Questions are locked because applicants have already answered them.</p>
                <ol class="mt-4 list-decimal space-y-1 pl-5 text-sm">@foreach($job->questions as $q)<li>{{ $q->prompt }} <span class="text-muted">({{ $types[$q->type] }}{{ $q->is_required ? ', required' : '' }})</span></li>@endforeach</ol>
            @else
            <div class="mt-5 space-y-3">
                <template x-for="(q, i) in questions" :key="q._k">
                    <div class="rounded-2xl border border-line bg-zinc-50/60 p-4">
                        <div class="grid gap-3 sm:grid-cols-[1fr_190px]">
                            <input class="input" :name="`questions[${i}][prompt]`" x-model="q.prompt" placeholder="Question" required>
                            <select class="input" :name="`questions[${i}][type]`" x-model="q.type">@foreach($types as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                        </div>
                        <template x-if="q.type === 'multiple_choice' || q.type === 'multi_select'">
                            <div class="mt-3 space-y-2">
                                <template x-for="(o, j) in q.options" :key="j">
                                    <div class="flex gap-2"><input class="input h-10" :name="`questions[${i}][options][${j}]`" x-model="q.options[j]" placeholder="Option"><button type="button" class="px-2 text-muted hover:text-rose-600" @click="q.options.splice(j, 1)" aria-label="Remove option"><x-icon name="x" class="size-4"/></button></div>
                                </template>
                                <button type="button" class="text-sm font-medium underline" @click="q.options.push('')">+ Add option</button>
                            </div>
                        </template>
                        <div class="mt-3 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm"><input type="hidden" :name="`questions[${i}][is_required]`" :value="q.is_required ? 1 : 0"><input type="checkbox" class="checkbox" x-model="q.is_required"> Required</label>
                            <div class="flex gap-1">
                                <button type="button" class="rounded-lg p-1.5 hover:bg-white" @click="move(i, -1)" aria-label="Move up"><x-icon name="chevron-down" class="size-4 rotate-180"/></button>
                                <button type="button" class="rounded-lg p-1.5 hover:bg-white" @click="move(i, 1)" aria-label="Move down"><x-icon name="chevron-down" class="size-4"/></button>
                                <button type="button" class="rounded-lg p-1.5 text-rose-600 hover:bg-white" @click="questions.splice(i, 1)" aria-label="Remove question"><x-icon name="trash" class="size-4"/></button>
                            </div>
                        </div>
                    </div>
                </template>
                <p x-show="!questions.length" class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-muted">No screening questions. Add one or load a template.</p>
            </div>
            @endunless
            <x-field-error name="questions"/>
        </section>
    </div>

    <aside data-fit="release" class="space-y-3 sm:space-y-4 xl:sticky xl:top-4 xl:h-fit">
        <section class="card card-pad space-y-3">
            @if($job->exists)<div class="flex items-center justify-between"><span class="text-sm text-muted">Status</span><x-status :status="$job->status"/></div>@endif
            <button name="intent" value="draft" class="btn btn-light w-full">{{ $job->exists ? 'Save changes' : 'Save draft' }}</button>
            @if(! $job->exists || in_array($job->status, ['draft', 'pending_payment', 'expired']))
                <button name="intent" value="publish" class="btn btn-dark w-full">Continue to publish <x-icon name="arrow-right" class="size-4"/></button>
                <p class="text-xs text-muted">Publishing requires the platform job-posting fee. You'll see the price before paying.</p>
            @endif
        </section>
        <section class="card card-pad text-sm text-muted">
            <p class="font-semibold text-ink">Tips</p>
            <ul class="mt-2 list-disc space-y-1 pl-4"><li>Be specific about deliverables and file formats.</li><li>Ask for portfolio links relevant to your brief.</li><li>Applicants are never rejected automatically.</li></ul>
        </section>
    </aside>
</form>
<script>
function jobBuilder(initial, templates, category) {
    let k = 0;
    const norm = q => ({ _k: k++, type: q.type, prompt: q.prompt, is_required: !!Number(q.is_required ?? 0) || q.is_required === true, options: Array.isArray(q.options) ? [...q.options] : [] });
    return {
        category,
        questions: (initial || []).map(norm),
        add() { this.questions.push(norm({ type: 'text', prompt: '', is_required: false, options: [] })); },
        useTemplate() { const t = templates[this.category] || []; const have = this.questions.map(q => q.prompt); t.forEach(q => { if (!have.includes(q.prompt)) this.questions.push(norm(q)); }); },
        move(i, d) { const j = i + d; if (j < 0 || j >= this.questions.length) return; const q = this.questions.splice(i, 1)[0]; this.questions.splice(j, 0, q); },
    };
}
</script>
</x-layouts.app>
