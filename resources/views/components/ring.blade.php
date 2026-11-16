@props(['value', 'label', 'size' => 170])
@php $r = 62; $c = 2 * M_PI * $r; $off = $c * (1 - max(0, min(100, $value)) / 100); @endphp
<div class="relative mx-auto" style="width: {{ $size }}px; height: {{ $size }}px">
    <svg viewBox="0 0 160 160" class="size-full -rotate-90" role="img" aria-label="{{ $label }}: {{ $value }}%">
        <circle cx="80" cy="80" r="{{ $r }}" fill="none" stroke="#18181b" stroke-width="18"/>
        <circle cx="80" cy="80" r="{{ $r }}" fill="none" stroke="#2f6bff" stroke-width="18" stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $off }}" stroke-linecap="round"/>
    </svg>
    <div class="absolute inset-[26%] grid place-items-center rounded-full bg-white shadow-[0_8px_20px_-8px_rgba(0,0,0,.25)]">
        <div class="text-center"><p class="font-display text-[26px] font-bold leading-none">{{ $value }}%</p><p class="mt-1 text-[9px] text-muted">{{ $label }}</p></div>
    </div>
</div>
