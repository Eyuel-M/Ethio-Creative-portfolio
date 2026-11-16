@props(['icon' => 'box', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white/60 px-6 py-14 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-2xl bg-white shadow-sm ring-1 ring-line"><x-icon :name="$icon" class="size-6 text-muted"/></span>
    <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
    @if($text)<p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>@endif
    @if($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
