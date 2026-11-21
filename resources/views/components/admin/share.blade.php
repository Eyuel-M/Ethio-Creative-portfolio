@props(['parts', 'empty' => 'No visits yet for this period.'])
{{--
    Part-to-whole bar for a few categories (e.g. traffic sources). parts: label => value, in a FIXED order so each
    category keeps its colour. Colours follow the reference categorical order; 2px gaps; legend doubles as the table.
--}}
@php
    $palette = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];
    $total = array_sum($parts);
    $i = 0;
@endphp
@if(! $total)
    <p class="py-6 text-center text-sm text-muted">{{ $empty }}</p>
@else
    <div class="flex h-4 w-full gap-[2px] overflow-hidden rounded-[4px]" role="img" aria-label="Share by category">
        @foreach($parts as $label => $v)
            @if($v > 0)<div class="h-full first:rounded-l-[4px] last:rounded-r-[4px]" style="width: {{ $v / $total * 100 }}%; background: {{ $palette[$loop->index % 8] }}" title="{{ $label }}: {{ number_format($v) }} ({{ round($v / $total * 100) }}%)"></div>@endif
        @endforeach
    </div>
    <table class="mt-4 w-full text-sm">
        <tbody class="divide-y divide-line">
            @foreach($parts as $label => $v)
                <tr>
                    <td class="py-2"><span class="flex items-center gap-2"><span class="size-2.5 shrink-0 rounded-sm" style="background: {{ $palette[$loop->index % 8] }}"></span>{{ $label }}</span></td>
                    <td class="py-2 text-right tabular-nums font-semibold">{{ number_format($v) }}</td>
                    <td class="w-16 py-2 text-right tabular-nums text-muted">{{ round($v / $total * 100) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
