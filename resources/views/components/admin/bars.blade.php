@props(['rows', 'format' => 'number', 'empty' => 'No data yet for this period.', 'color' => '#2a78d6', 'valueLabel' => null])
{{-- Ranked horizontal bars. rows: list of ['label', 'value', 'sub' => ?, 'href' => ?, 'extra' => ?] --}}
@php
    $max = max(1, ...(array_column($rows, 'value') ?: [0]));
    $fmt = fn ($v) => match ($format) { 'money' => \App\Support\Money::format((int) $v, 'ETB'), 'seconds' => $v >= 60 ? floor($v / 60).'m '.($v % 60).'s' : round($v).'s', 'pct' => round($v, 1).'%', default => number_format($v) };
@endphp
@if(! $rows)
    <p class="py-6 text-center text-sm text-muted">{{ $empty }}</p>
@else
    @if($valueLabel)<p class="mb-2 text-right text-[11px] font-medium uppercase tracking-wider text-faint">{{ $valueLabel }}</p>@endif
    <ul class="space-y-3">
        @foreach($rows as $r)
            <li title="{{ $r['label'] }}: {{ $fmt($r['value']) }}">
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    @if(! empty($r['href']))<a href="{{ $r['href'] }}" class="min-w-0 truncate font-medium hover:underline">{{ $r['label'] }}</a>@else<span class="min-w-0 truncate font-medium">{{ $r['label'] }}</span>@endif
                    <span class="shrink-0 tabular-nums text-ink-soft">@if(isset($r['extra']))<span class="mr-2 text-xs text-muted">{{ $r['extra'] }}</span>@endif<span class="font-semibold text-ink">{{ $fmt($r['value']) }}</span></span>
                </div>
                @if(! empty($r['sub']))<p class="truncate text-xs text-muted">{{ $r['sub'] }}</p>@endif
                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full" style="width: {{ max(1, $r['value'] / $max * 100) }}%; background: {{ $color }}"></div></div>
            </li>
        @endforeach
    </ul>
@endif
