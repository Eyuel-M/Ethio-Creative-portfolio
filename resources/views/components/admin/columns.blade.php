@props(['series', 'label' => 'Value', 'format' => 'number', 'height' => 180, 'color' => '#2a78d6'])
{{--
    Single-series column chart (e.g. daily visitors). series: list of ['label' => 'Nov 3', 'value' => 12].
    Marks: columns ≤ 24px with a 4px rounded top, 2px surface gap, hairline grid, hover tooltip, table view below.
--}}
@php
    $vals = array_column($series, 'value');
    $max = max(1, ...($vals ?: [0]));
    $step = $max <= 5 ? 1 : (10 ** floor(log10($max / 2)));
    $top = ceil($max / $step / 2) * $step * 2; $top = max($top, $max);
    $fmt = fn ($v) => match ($format) {
        'money' => \App\Support\Money::format((int) $v, 'ETB'),
        'seconds' => $v >= 60 ? floor($v / 60).'m '.($v % 60).'s' : $v.'s',
        default => $v >= 10000 ? round($v / 1000, 1).'K' : number_format($v),
    };
    $n = count($series);
@endphp
<div x-data="{ tip: null, table: false }" class="relative">
    <div class="flex gap-3">
        <div class="flex w-10 shrink-0 flex-col justify-between text-right text-[11px] tabular-nums text-muted" style="height: {{ $height }}px">
            <span>{{ $fmt($top) }}</span><span>{{ $fmt($top / 2) }}</span><span>0</span>
        </div>
        <div class="relative min-w-0 flex-1" style="height: {{ $height }}px">
            <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                <div class="h-px bg-zinc-200"></div><div class="h-px bg-zinc-100"></div><div class="h-px bg-zinc-300"></div>
            </div>
            <div class="absolute inset-0 flex items-end gap-[2px]" role="img" aria-label="{{ $label }} chart">
                @foreach($series as $i => $p)
                    <div class="group flex h-full min-w-0 flex-1 cursor-default items-end justify-center" @mouseenter="tip = {{ $i }}" @mouseleave="tip = null">
                        <div class="w-full max-w-6 rounded-t-[4px] transition-opacity group-hover:opacity-80" style="height: {{ $p['value'] > 0 ? max(2, $p['value'] / $top * 100) : 0 }}%; background: {{ $color }}"></div>
                        <div x-show="tip === {{ $i }}" x-cloak class="pointer-events-none absolute bottom-full z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-ink px-2.5 py-1.5 text-xs text-white shadow-lg" style="left: {{ ($i + 0.5) / max(1, $n) * 100 }}%">
                            <span class="text-white/70">{{ $p['label'] }}</span> · <span class="font-semibold">{{ $fmt($p['value']) }}</span> {{ strtolower($label) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="ml-[52px] mt-2 flex justify-between text-[11px] text-muted">
        <span>{{ $series[0]['label'] ?? '' }}</span>@if($n > 2)<span>{{ $series[intdiv($n, 2)]['label'] }}</span>@endif<span>{{ $series[$n - 1]['label'] ?? '' }}</span>
    </div>
    <button type="button" @click="table = !table" class="mt-2 text-xs font-medium text-muted hover:text-ink" x-text="table ? 'Hide table' : 'Show as table'"></button>
    <div x-show="table" x-cloak class="mt-2 max-h-56 overflow-y-auto rounded-xl border border-line">
        <table class="w-full text-xs"><thead class="sticky top-0 bg-zinc-50"><tr><th class="px-3 py-2 text-left font-medium text-muted">Date</th><th class="px-3 py-2 text-right font-medium text-muted">{{ $label }}</th></tr></thead>
            <tbody class="divide-y divide-line">@foreach(array_reverse($series) as $p)<tr><td class="px-3 py-1.5">{{ $p['label'] }}</td><td class="px-3 py-1.5 text-right tabular-nums">{{ $fmt($p['value']) }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
