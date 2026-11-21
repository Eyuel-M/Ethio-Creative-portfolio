@props(['title', 'subtitle' => null, 'back' => null])
<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if($back)<a href="{{ $back[1] }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> {{ $back[0] }}</a>@endif
        <h1 class="font-display text-2xl font-bold tracking-tight sm:text-[28px]">{{ $title }}</h1>
        @if($subtitle)<p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
