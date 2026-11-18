<x-layouts.app title="Publish job">
<section class="card card-pad">
    <a href="{{ route('client.jobs.edit', $job) }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> Edit job</a>
    <h1 class="mt-3 font-display text-3xl font-bold">Publish “{{ $job->title }}”</h1>
    <p class="mt-1 text-muted">Choose a listing option. Your job goes live as soon as the provider confirms payment.</p>
</section>
<form method="POST" action="{{ route('client.jobs.checkout', $job) }}" class="grid gap-4 lg:grid-cols-[1fr_340px]" x-data="{ p: 'job_post_standard' }">@csrf
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($products as $prod)
            <label class="cursor-pointer rounded-[22px] border-2 bg-white p-7 transition" :class="p === '{{ $prod->code }}' ? 'border-ink shadow-[0_30px_60px_-30px_rgba(12,12,16,.35)]' : 'border-line'">
                <input type="radio" name="product" value="{{ $prod->code }}" x-model="p" class="sr-only">
                <div class="flex items-center justify-between"><p class="font-display text-2xl font-medium text-ink-soft">{{ $prod->name }}</p><span class="grid size-6 place-items-center rounded-full border-2" :class="p === '{{ $prod->code }}' ? 'border-ink bg-ink text-white' : 'border-zinc-300'"><x-icon name="check" class="size-3.5"/></span></div>
                <p class="mt-3 flex items-baseline gap-2"><span class="font-display text-4xl font-bold">{{ $prod->priceLabel() }}</span><span class="text-ink-soft">/ {{ $prod->duration_days }} days</span></p>
                <ul class="mt-6 space-y-3">@foreach($prod->benefits as $b)<li class="flex gap-3 text-[15px]"><x-icon name="check" class="size-5 shrink-0 text-emerald-600"/>{{ $b }}</li>@endforeach</ul>
            </label>
        @endforeach
    </div>
    <aside class="card card-pad h-fit space-y-4">
        <h2 class="font-semibold">Summary</h2>
        <dl class="space-y-2 text-sm"><div class="flex justify-between"><dt class="text-muted">Category</dt><dd>{{ $job->category?->name }}</dd></div><div class="flex justify-between"><dt class="text-muted">Budget</dt><dd>{{ $job->budgetLabel() }}</dd></div><div class="flex justify-between"><dt class="text-muted">Screening questions</dt><dd>{{ $job->questions->count() }}</dd></div></dl>
        <button class="btn btn-dark h-12 w-full">Continue to payment</button>
    </aside>
</form>
</x-layouts.app>
