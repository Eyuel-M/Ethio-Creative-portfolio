@props(['items', 'active', 'param' => 'status'])
{{-- items: value => [label, count|null] --}}
<nav class="flex gap-1 overflow-x-auto border-b border-line" aria-label="Filter">
    @foreach($items as $v => [$label, $n])
        <a href="{{ request()->fullUrlWithQuery([$param => $v ?: null, 'page' => null]) }}" @if($active === $v) aria-current="page" @endif
           class="flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition {{ $active === $v ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-ink' }}">
            {{ $label }} @if($n !== null)<span class="rounded-full px-1.5 py-px text-[11px] tabular-nums {{ $active === $v ? 'bg-ink text-white' : 'bg-zinc-100 text-ink-soft' }}">{{ $n }}</span>@endif
        </a>
    @endforeach
</nav>
