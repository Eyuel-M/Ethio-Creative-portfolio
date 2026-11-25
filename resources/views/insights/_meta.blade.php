{{-- Byline row under a post title. $light: on top of a dark cover image. --}}
@php $byAuthor = $post->author && ! $post->author_label; @endphp
<div class="mt-7 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm {{ ($post->opt('layout') === 'minimal' && ! $light) ? 'justify-center' : '' }}">
    <span class="flex items-center gap-2.5 font-semibold">
        @if($byAuthor)<x-avatar :user="$post->author" size="size-9" class="text-xs {{ $light ? 'ring-2 ring-white/70' : '' }}"/>@else<span class="grid size-9 place-items-center rounded-full {{ $light ? 'bg-white text-ink' : 'bg-ink text-white' }}"><x-logo-mark class="size-4"/></span>@endif
        {{ $post->byline() }}
    </span>
    <span class="{{ $light ? 'text-white/70' : 'text-muted' }}">
        @if($post->published_at)<time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('F j, Y') }}</time>@else Not published yet @endif
        @if($post->shows('reading_time')) · {{ $post->reading_minutes }} min read @endif
        @if($post->audience !== 'everyone') · {{ \App\Models\Post::AUDIENCES[$post->audience] }} @endif
    </span>
</div>
