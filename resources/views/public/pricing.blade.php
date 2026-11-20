<x-layouts.public title="Pricing">
@php
    $freelancer = collect([(object) ['code' => 'free', 'name' => 'Free', 'tagline' => 'Starter portfolio', 'priceLabel' => '0 ETB', 'duration_days' => null, 'benefits' => ['Public profile & directory listing', config('marketplace.portfolio_limits.free').' portfolio case studies', 'Apply to unlimited jobs', 'Basic profile & project views', 'Private CBE / Telebirr details']]])
        ->concat(($products['freelancer'] ?? collect())->map(fn ($p) => (object) ['code' => $p->code, 'name' => $p->name, 'tagline' => $p->tagline, 'priceLabel' => $p->priceLabel(), 'duration_days' => $p->duration_days, 'benefits' => $p->benefits]));
    $client = ($products['client'] ?? collect())->map(fn ($p) => (object) ['code' => $p->code, 'name' => $p->name, 'tagline' => $p->tagline, 'priceLabel' => $p->priceLabel(), 'duration_days' => $p->duration_days, 'benefits' => $p->benefits]);
@endphp
<section class="relative overflow-hidden" x-data="{ tab: 'freelancer' }">
    <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent_85%)]"></div>
    <div class="pointer-events-none absolute -left-40 top-20 size-96 rounded-full bg-rose-200/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-40 top-60 size-96 rounded-full bg-emerald-200/30 blur-3xl"></div>

    <div class="relative mx-auto max-w-[1240px] px-4 pb-24 pt-16 sm:px-6 sm:pt-24">
        <div class="text-center">
            <h1 class="font-display text-6xl font-semibold tracking-[-0.04em] sm:text-8xl">Pricing</h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-muted">Transparent fees for the platform's own services. We never charge to reveal contact details and never take a cut of what clients pay you.</p>
            <div class="mt-8 inline-flex rounded-full border border-line bg-white p-1 text-sm shadow-sm" role="tablist">
                <button role="tab" :aria-selected="tab === 'freelancer'" @click="tab = 'freelancer'" :class="tab === 'freelancer' ? 'bg-ink text-white' : 'text-ink-soft'" class="rounded-full px-5 py-2 font-medium transition">For freelancers</button>
                <button role="tab" :aria-selected="tab === 'client'" @click="tab = 'client'" :class="tab === 'client' ? 'bg-ink text-white' : 'text-ink-soft'" class="rounded-full px-5 py-2 font-medium transition">For clients</button>
            </div>
        </div>

        @foreach(['freelancer' => $freelancer, 'client' => $client] as $aud => $plans)
            <div x-show="tab === '{{ $aud }}'" @if($aud === 'client') x-cloak @endif class="mx-auto mt-14 grid gap-5 {{ $plans->count() >= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2 max-w-4xl' }}">
                @foreach($plans as $i => $plan)
                    @php $hl = $i === 1; @endphp
                    <div class="flex flex-col rounded-[22px] border p-7 sm:p-8 {{ $hl ? 'border-line bg-white shadow-[0_30px_60px_-30px_rgba(12,12,16,.35)]' : 'border-line bg-[#f7f7f8]' }}">
                        <p class="font-display text-[26px] font-medium text-ink-soft">{{ $plan->name }}</p>
                        <p class="mt-3 flex items-baseline gap-2"><span class="font-display text-[44px] font-bold tracking-tight">{{ $plan->priceLabel }}</span><span class="text-ink-soft">{{ $plan->duration_days ? 'per '.$plan->duration_days.' days' : 'forever' }}</span></p>
                        <p class="mt-1 text-sm text-muted">{{ $plan->tagline }}</p>
                        <ul class="mt-8 space-y-4">
                            @foreach($plan->benefits ?? [] as $b)
                                <li class="flex gap-3 text-[15px] {{ $hl ? 'font-medium' : 'text-ink-soft' }}"><x-icon name="check" class="mt-0.5 size-5 shrink-0 text-emerald-600"/>{{ $b }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-auto pt-10">
                            @php $href = $aud === 'client' ? (auth()->check() ? route('client.jobs.create') : route('register', ['role' => 'client'])) : (auth()->check() ? route('billing.index') : route('register', ['role' => 'freelancer'])); @endphp
                            <a href="{{ $href }}" class="btn h-12 w-full text-base font-medium {{ $hl ? 'btn-dark' : 'btn-light' }}">{{ $plan->code === 'free' ? 'Get Started' : ($aud === 'client' ? 'Post a job' : 'Upgrade') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="mx-auto mt-14 grid max-w-4xl gap-4 rounded-2xl border border-line bg-white p-6 text-sm sm:grid-cols-3">
            <div class="flex gap-3"><x-icon name="lock" class="size-5 shrink-0 text-ink"/><p><span class="font-semibold">No contact unlocks.</span> <span class="text-muted">Details are shared only after both parties confirm an agreement.</span></p></div>
            <div class="flex gap-3"><x-icon name="bank" class="size-5 shrink-0 text-ink"/><p><span class="font-semibold">No commission.</span> <span class="text-muted">Project payments go directly to the freelancer's CBE or Telebirr.</span></p></div>
            <div class="flex gap-3"><x-icon name="megaphone" class="size-5 shrink-0 text-ink"/><p><span class="font-semibold">Promotion is labelled.</span> <span class="text-muted">Featured placement never guarantees work, applicants or hires.</span></p></div>
        </div>
    </div>
</section>
</x-layouts.public>
