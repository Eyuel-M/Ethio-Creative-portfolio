@props(['series', 'label' => 'Value', 'height' => 220])
@php
    $w = 640; $h = $height; $padL = 34; $padR = 12; $padT = 16; $padB = 26;
    $vals = array_column($series, 'value');
    $max = max(1, max($vals));
    $step = $max <= 4 ? 1 : (int) ceil($max / 4);
    $top = $step * (int) ceil($max / $step);
    $n = count($series);
    $x = fn ($i) => $padL + ($n > 1 ? $i * ($w - $padL - $padR) / ($n - 1) : 0);
    $y = fn ($v) => $padT + ($h - $padT - $padB) * (1 - $v / $top);
    $pts = collect($series)->map(fn ($p, $i) => [$x($i), $y($p['value'])])->all();
    // Smooth curve (monotone-ish via Catmull-Rom → Bezier, clamped to plot area)
    $d = 'M'.$pts[0][0].','.$pts[0][1];
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)]; $p1 = $pts[$i]; $p2 = $pts[$i + 1]; $p3 = $pts[min($n - 1, $i + 2)];
        $c1x = $p1[0] + ($p2[0] - $p0[0]) / 6; $c1y = min($y(0), max($padT, $p1[1] + ($p2[1] - $p0[1]) / 6));
        $c2x = $p2[0] - ($p3[0] - $p1[0]) / 6; $c2y = min($y(0), max($padT, $p2[1] - ($p3[1] - $p1[1]) / 6));
        $d .= " C$c1x,$c1y $c2x,$c2y {$p2[0]},{$p2[1]}";
    }
    $area = $d." L{$pts[$n - 1][0]},".$y(0)." L{$pts[0][0]},".$y(0).' Z';
    $gid = 'g'.substr(md5($label.$n), 0, 6);
@endphp
<div x-data="{ i: null, pts: @js(collect($series)->map(fn ($p, $k) => ['x' => $pts[$k][0], 'y' => $pts[$k][1], 'v' => $p['value'], 'm' => $p['month']])) }" class="relative" @mouseleave="i = null">
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full overflow-visible" role="img" aria-label="{{ $label }} per month, last 12 months">
        <defs><linearGradient id="{{ $gid }}" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#2f6bff" stop-opacity=".22"/><stop offset="100%" stop-color="#2f6bff" stop-opacity="0"/></linearGradient></defs>
        @for($t = 0; $t <= $top; $t += $step)
            <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" stroke="#ebebef" stroke-width="1" @if($t) stroke-dasharray="3 4" @endif/>
            <text x="{{ $padL - 8 }}" y="{{ $y($t) + 3.5 }}" text-anchor="end" font-size="10" fill="#a1a1aa">{{ $t }}</text>
        @endfor
        <path d="{{ $area }}" fill="url(#{{ $gid }})"/>
        <path d="{{ $d }}" fill="none" stroke="#2f6bff" stroke-width="2" stroke-linecap="round"/>
        @foreach($series as $k => $p)
            <text x="{{ $pts[$k][0] }}" y="{{ $h - 6 }}" text-anchor="middle" font-size="10" fill="#a1a1aa">{{ strtoupper($p['label']) }}</text>
        @endforeach
        <g x-show="i !== null" style="display:none">
            <line :x1="i !== null ? pts[i].x : 0" :x2="i !== null ? pts[i].x : 0" y1="{{ $padT }}" y2="{{ $y(0) }}" stroke="#2f6bff" stroke-width="1"/>
            <circle :cx="i !== null ? pts[i].x : 0" :cy="i !== null ? pts[i].y : 0" r="5" fill="#2f6bff" stroke="#fff" stroke-width="2"/>
        </g>
        @foreach($series as $k => $p)
            <rect x="{{ $pts[$k][0] - ($w - $padL - $padR) / max(1, $n - 1) / 2 }}" y="0" width="{{ ($w - $padL - $padR) / max(1, $n - 1) }}" height="{{ $h }}" fill="transparent" @mouseenter="i = {{ $k }}" @focus="i = {{ $k }}" tabindex="-1"/>
        @endforeach
    </svg>
    <div x-show="i !== null" x-cloak class="pointer-events-none absolute -translate-x-1/2 -translate-y-full rounded-xl bg-ink px-3 py-2 text-center text-white shadow-lg"
         :style="i === null ? '' : `left:${pts[i].x / {{ $w }} * 100}%; top:${pts[i].y / {{ $h }} * 100 - 4}%`">
        <p class="text-[10px] text-white/60" x-text="i !== null && pts[i].m"></p>
        <p class="text-sm font-semibold"><span x-text="i !== null && pts[i].v"></span> {{ strtolower($label) }}</p>
    </div>
    <table class="sr-only"><caption>{{ $label }} per month</caption><tbody>@foreach($series as $p)<tr><th>{{ $p['month'] }}</th><td>{{ $p['value'] }}</td></tr>@endforeach</tbody></table>
</div>
