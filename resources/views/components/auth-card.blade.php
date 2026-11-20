@props(['title', 'subtitle' => null])
<x-layouts.public :title="$title">
    <section class="relative overflow-hidden">
        <div class="hero-gradient absolute inset-x-0 top-0 h-[420px]"></div>
        <div class="relative grid items-center gap-10 px-4 py-14 sm:px-8 lg:grid-cols-[1fr_440px] lg:gap-16 lg:py-20 xl:grid-cols-[1fr_480px]">
            <div class="hidden max-w-2xl flex-col justify-center lg:flex">
                <span class="eyebrow w-fit">EthioCreative</span>
                <h1 class="mt-5 font-display text-5xl font-bold leading-[1.05] tracking-tight">Where Ethiopian creative work gets discovered.</h1>
                <ul class="mt-8 space-y-3 text-ink-soft">
                    <li class="flex gap-3"><x-icon name="check-circle" class="size-5 text-leaf-dark"/> Free portfolio with structured case studies</li>
                    <li class="flex gap-3"><x-icon name="check-circle" class="size-5 text-leaf-dark"/> Clients pay you directly — no commission</li>
                    <li class="flex gap-3"><x-icon name="check-circle" class="size-5 text-leaf-dark"/> Your details stay private until you both agree</li>
                </ul>
            </div>
            <div class="card p-7 shadow-[0_30px_60px_-30px_rgba(12,12,16,.3)] sm:p-9">
                <h2 class="font-display text-[28px] font-bold tracking-tight">{{ $title }}</h2>
                @if($subtitle)<p class="mt-1.5 text-sm text-muted">{{ $subtitle }}</p>@endif
                <div class="mt-7">{{ $slot }}</div>
            </div>
        </div>
    </section>
</x-layouts.public>
