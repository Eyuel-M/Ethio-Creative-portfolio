@props(['post', 'size' => 'default'])
{{-- An Insights post as a card. size: default (grid) · compact (related / dark band) --}}
@php $compact = $size === 'compact'; @endphp
<article {{ $attributes->merge(['class' => 'group relative flex min-w-0 flex-col']) }}>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $compact ? 'aspect-[16/10]' : 'aspect-[16/10]' }} {{ $post->accent('grad') }}">
        @if($post->coverUrl())
            <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
        @else
            <div class="absolute inset-0 grid-lines opacity-60"></div>
            <span class="absolute bottom-4 left-4 grid size-11 place-items-center rounded-2xl bg-white/80 shadow-sm backdrop-blur {{ $post->accent('text') }}"><x-icon :name="$post->typeIcon()" class="size-5"/></span>
        @endif
        <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold shadow-sm {{ $post->accent('text') }}"><x-icon :name="$post->typeIcon()" class="size-3.5"/> {{ $post->typeLabel() }}</span>
        @if($post->featured_at)<span class="absolute right-3 top-3 rounded-full bg-ink/80 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">Pinned</span>@endif
    </div>
    <div class="flex flex-1 flex-col pt-4">
        <p class="flex flex-wrap items-center gap-x-2 text-xs text-muted">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time>
            @if($post->shows('reading_time'))<span aria-hidden="true">·</span><span>{{ $post->reading_minutes }} min read</span>@endif
            @if($post->audience !== 'everyone')<span aria-hidden="true">·</span><span>{{ \App\Models\Post::AUDIENCES[$post->audience] }}</span>@endif
        </p>
        <h3 class="mt-1.5 font-display {{ $compact ? 'text-lg' : 'text-xl' }} font-bold leading-snug tracking-tight">
            <a href="{{ route('insights.show', $post) }}" class="after:absolute after:inset-0 group-hover:underline decoration-2 underline-offset-4">{{ $post->title }}</a>
        </h3>
        @if($post->excerpt && ! $compact)<p class="mt-2 line-clamp-2 text-[15px] leading-relaxed text-ink-soft">{{ $post->excerpt }}</p>@endif
        @unless($compact)
            <p class="mt-auto flex items-center gap-2 pt-4 text-sm font-medium">
                @if($post->author && ! $post->author_label)<x-avatar :user="$post->author" size="size-6" class="text-[9px]"/>@else<span class="grid size-6 place-items-center rounded-full bg-ink text-white"><x-logo-mark class="size-3.5"/></span>@endif
                <span class="truncate">{{ $post->byline() }}</span>
            </p>
        @endunless
    </div>
</article>
