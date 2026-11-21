@props(['title' => null, 'subtitle' => null, 'pad' => true])
<section {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-white']) }}>
    @if($title)
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
            <div><h2 class="text-[15px] font-semibold">{{ $title }}</h2>@if($subtitle)<p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>@endif</div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $pad ? 'p-5' : '' }}">{{ $slot }}</div>
</section>
