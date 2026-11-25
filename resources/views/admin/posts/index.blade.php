@php use App\Models\Post; @endphp
<x-layouts.admin title="Posts">
<x-admin.header title="Insights posts" subtitle="Studies, guides, product updates and stories for members and visitors. Published posts appear on the public Insights page.">
    <x-slot:actions>
        <a href="{{ route('insights.index') }}" target="_blank" class="btn btn-light"><x-icon name="arrow-up-right" class="size-4"/> View Insights</a>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-dark"><x-icon name="plus" class="size-4"/> New post</a>
    </x-slot:actions>
</x-admin.header>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <x-admin.stat label="Live posts" :value="number_format($totals['published'])"/>
    <x-admin.stat label="Published in the last 30 days" :value="number_format($totals['month'])"/>
    <x-admin.stat label="Total reads" :value="number_format($totals['views'])" hint="Unique readers per session, admins excluded"/>
</div>

<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$f['status'] ?? ''" :items="['' => ['All', $counts['']], 'published' => ['Live', $counts['published']], 'scheduled' => ['Scheduled', $counts['scheduled']], 'draft' => ['Drafts', $counts['draft']], 'archived' => ['Archived', $counts['archived']]]"/>
    <form class="flex flex-wrap items-center gap-2 border-b border-line p-3 sm:px-5">
        @if(! empty($f['status']))<input type="hidden" name="status" value="{{ $f['status'] }}">@endif
        <div class="relative min-w-[220px] flex-1 sm:max-w-sm"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/><input name="q" value="{{ $f['q'] ?? '' }}" class="input h-10 pl-9" placeholder="Search titles"></div>
        <div class="w-44"><select name="type" class="input h-10" onchange="this.form.submit()"><option value="">All types</option>@foreach(Post::TYPES as $v => [$tl])<option value="{{ $v }}" @selected(($f['type'] ?? '') === $v)>{{ $tl }}</option>@endforeach</select></div>
        <div class="w-44"><select name="sort" class="input h-10" onchange="this.form.submit()">@foreach(['updated' => 'Recently edited', 'published' => 'Publish date', 'views' => 'Most read', 'title' => 'Title A–Z'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'updated') === $v)>{{ $l }}</option>@endforeach</select></div>
        <button class="btn btn-light h-10">Apply</button>
    </form>

    <ul class="divide-y divide-line">
        @forelse($posts as $p)
            @php $state = $p->state(); @endphp
            <li class="flex flex-col gap-4 p-4 transition hover:bg-zinc-50/70 sm:flex-row sm:items-center sm:px-5">
                <a href="{{ route('admin.posts.edit', $p) }}" class="relative block aspect-[16/10] w-full shrink-0 overflow-hidden rounded-xl bg-gradient-to-br sm:w-40 {{ $p->accent('grad') }}">
                    @if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover" loading="lazy">@else<span class="grid size-full place-items-center {{ $p->accent('text') }}"><x-icon :name="$p->typeIcon()" class="size-7"/></span>@endif
                    @if($p->featured_at)<span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-semibold text-ink shadow-sm"><x-icon name="sparkles" class="size-3"/> Pinned</span>@endif
                </a>
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold {{ $p->accent('soft') }} {{ $p->accent('text') }}"><x-icon :name="$p->typeIcon()" class="size-3.5"/> {{ $p->typeLabel() }}</span>
                        <x-status :status="$state"/>
                        @if($p->audience !== 'everyone')<span class="text-muted">{{ Post::AUDIENCES[$p->audience] }}</span>@endif
                    </p>
                    <a href="{{ route('admin.posts.edit', $p) }}" class="mt-1.5 block truncate font-display text-[17px] font-bold hover:underline">{{ $p->title }}</a>
                    @if($p->excerpt)<p class="mt-0.5 line-clamp-1 text-sm text-muted">{{ $p->excerpt }}</p>@endif
                    <p class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted">
                        <span>{{ $p->byline() }}</span>
                        <span>@if($state === 'scheduled')Goes live {{ $p->published_at->format('M j, Y · g:ia') }}@elseif($p->published_at && $state === 'published')Published {{ $p->published_at->format('M j, Y') }}@else Edited {{ $p->updated_at->diffForHumans() }}@endif</span>
                        <span>{{ $p->reading_minutes }} min read · {{ $p->blocks_count }} blocks</span>
                        @if($state === 'published')<span class="inline-flex items-center gap-1"><x-icon name="eye" class="size-3.5"/> {{ number_format($p->views) }}</span>@endif
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('admin.posts.edit', $p) }}" class="btn btn-sm btn-dark"><x-icon name="pen" class="size-4"/> Edit</a>
                    <a href="{{ route('insights.show', $p) }}" target="_blank" class="btn btn-sm btn-light" title="{{ $p->isPublic() ? 'View live' : 'Preview' }}"><x-icon name="eye" class="size-4"/></a>
                    <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                        <button type="button" @click="open = !open" class="btn btn-sm btn-light w-9 px-0" aria-label="More actions"><x-icon name="more" class="size-4"/></button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right class="absolute right-0 top-full z-30 mt-2 w-52 rounded-2xl border border-line bg-white p-1.5 text-left shadow-xl">
                            @php $item = 'flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50'; @endphp
                            @if($p->status === 'published')
                                <form method="POST" action="{{ route('admin.posts.status', $p) }}">@csrf<input type="hidden" name="action" value="unpublish"><button class="{{ $item }}"><x-icon name="eye" class="size-4 text-muted"/> Unpublish</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.posts.status', $p) }}">@csrf<input type="hidden" name="action" value="publish"><button class="{{ $item }}"><x-icon name="arrow-up-right" class="size-4 text-muted"/> Publish now</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.posts.status', $p) }}">@csrf<input type="hidden" name="action" value="{{ $p->featured_at ? 'unfeature' : 'feature' }}"><button class="{{ $item }}"><x-icon name="sparkles" class="size-4 text-muted"/> {{ $p->featured_at ? 'Unpin' : 'Pin to top' }}</button></form>
                            <form method="POST" action="{{ route('admin.posts.duplicate', $p) }}">@csrf<button class="{{ $item }}"><x-icon name="copy" class="size-4 text-muted"/> Duplicate</button></form>
                            @if($p->status !== 'archived')
                                <form method="POST" action="{{ route('admin.posts.status', $p) }}">@csrf<input type="hidden" name="action" value="archive"><button class="{{ $item }}"><x-icon name="box" class="size-4 text-muted"/> Archive</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.posts.status', $p) }}">@csrf<input type="hidden" name="action" value="restore"><button class="{{ $item }}"><x-icon name="refresh" class="size-4 text-muted"/> Restore as draft</button></form>
                            @endif
                            <div class="my-1 h-px bg-line"></div>
                            <form method="POST" action="{{ route('admin.posts.destroy', $p) }}" onsubmit="return confirm('Delete this post permanently? This cannot be undone.')">@csrf @method('DELETE')<button class="{{ $item }} text-rose-600 hover:bg-rose-50"><x-icon name="trash" class="size-4"/> Delete</button></form>
                        </div>
                    </div>
                </div>
            </li>
        @empty
            <li class="px-5 py-14 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 text-muted"><x-icon name="pen" class="size-6"/></span>
                <p class="mt-3 font-semibold">{{ ($f['q'] ?? null) || ($f['type'] ?? null) || ($f['status'] ?? null) ? 'No posts match' : 'No posts yet' }}</p>
                <p class="mt-1 text-sm text-muted">Share a study, a how-to guide or what's new on the platform.</p>
                <a href="{{ route('admin.posts.create') }}" class="btn btn-dark mt-4"><x-icon name="plus" class="size-4"/> Write the first post</a>
            </li>
        @endforelse
    </ul>
    @if($posts->hasPages())<div class="border-t border-line p-4">{{ $posts->links() }}</div>@endif
</x-admin.panel>
</x-layouts.admin>
