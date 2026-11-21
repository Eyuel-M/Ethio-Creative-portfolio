@props(['label', 'value', 'delta' => null, 'hint' => null, 'href' => null, 'tone' => null])
{{-- delta: percentage change vs the previous period (null = not shown) --}}
<{{ $href ? 'a href='.$href : 'div' }} {{ $attributes->merge(['class' => 'block rounded-2xl border border-line bg-white p-4 sm:p-5 '.($href ? 'transition hover:border-zinc-300 hover:shadow-sm' : '')]) }}>
    <p class="flex items-center gap-1.5 text-[12px] font-medium uppercase tracking-wider text-muted">
        @if($tone === 'alert')<span class="size-1.5 rounded-full bg-rose-500"></span>@elseif($tone === 'ok')<span class="size-1.5 rounded-full bg-emerald-500"></span>@endif
        {{ $label }}
    </p>
    <p class="mt-1.5 font-display text-2xl font-bold tabular-nums tracking-tight">{{ $value }}</p>
    @if($delta !== null || $hint)
        <p class="mt-1 flex items-center gap-1.5 text-xs text-muted">
            @if($delta !== null)
                <span class="inline-flex items-center gap-0.5 font-semibold {{ $delta > 0 ? 'text-emerald-700' : ($delta < 0 ? 'text-rose-700' : 'text-muted') }}">{{ $delta > 0 ? '▲' : ($delta < 0 ? '▼' : '•') }} {{ abs($delta) }}%</span>
            @endif
            {{ $hint }}
        </p>
    @endif
</{{ $href ? 'a' : 'div' }}>
