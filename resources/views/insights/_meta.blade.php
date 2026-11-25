{{-- Date and reading time under a post title (posts are from the company, so no author byline). $light: on top of a dark cover image. --}}
<div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm {{ $light ? 'text-white/75' : 'text-muted' }} {{ ($post->opt('layout') === 'minimal' && ! $light) ? 'justify-center' : '' }}">
    <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="size-4"/>@if($post->published_at)<time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('F j, Y') }}</time>@else Not published yet @endif</span>
    @if($post->shows('reading_time'))<span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="size-4"/>{{ $post->reading_minutes }} min read</span>@endif
    @if($post->audience !== 'everyone')<span class="inline-flex items-center gap-1.5"><x-icon name="users" class="size-4"/>{{ \App\Models\Post::AUDIENCES[$post->audience] }}</span>@endif
</div>
