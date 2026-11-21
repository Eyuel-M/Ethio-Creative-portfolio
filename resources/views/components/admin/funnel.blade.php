@props(['steps', 'color' => '#2a78d6'])
{{-- steps: list of [label, value, hint?]. Bar width = share of the first step; each step shows conversion from the previous. --}}
@php $first = max(1, $steps[0][1] ?? 1); @endphp
<ol class="space-y-3">
    @foreach($steps as $i => $s)
        @php $prev = $i ? max(1, $steps[$i - 1][1]) : null; @endphp
        <li>
            <div class="flex items-baseline justify-between gap-3 text-sm">
                <span class="font-medium"><span class="mr-1.5 text-muted">{{ $i + 1 }}.</span>{{ $s[0] }}@if(! empty($s[2]))<span class="ml-1.5 text-xs font-normal text-muted">{{ $s[2] }}</span>@endif</span>
                <span class="shrink-0 tabular-nums"><span class="font-semibold">{{ number_format($s[1]) }}</span>@if($prev !== null)<span class="ml-2 text-xs text-muted">{{ round($s[1] / $prev * 100, 1) }}% of previous</span>@endif</span>
            </div>
            <div class="mt-1.5 h-6 overflow-hidden rounded-[4px] bg-zinc-100"><div class="flex h-full items-center rounded-[4px] px-2 text-[11px] font-semibold text-white" style="width: {{ min(100, max(2, $s[1] / $first * 100)) }}%; background: {{ $color }}">@if($s[1] / $first > 0.12){{ round($s[1] / $first * 100, 1) }}%@endif</div></div>
        </li>
    @endforeach
</ol>
