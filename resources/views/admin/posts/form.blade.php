@php
    use App\Models\Post;
    use App\Models\PostBlock;
    use App\Support\Media;
    $url = fn ($p) => $p ? Media::url($p) : null;
    $imageTypes = ['image', 'image_text'];
    $toArray = fn (array $b) => [
        'type' => $b['type'] ?? 'text', 'title' => $b['title'] ?? '', 'body' => $b['body'] ?? '', 'caption' => $b['caption'] ?? '',
        'url' => $b['url'] ?? '', 'alt' => $b['alt'] ?? '', 'size' => $b['size'] ?? '',
        'existing' => $b['existing'] ?? null, 'preview' => in_array($b['type'] ?? '', $imageTypes, true) ? $url($b['existing'] ?? null) : null,
        'gallery' => collect($b['gallery'] ?? [])->map(fn ($g) => ['path' => $g['path'], 'alt' => $g['alt'] ?? '', 'url' => $url($g['path'])])->values()->all(),
        'items' => collect($b['items'] ?? [])->filter(fn ($it) => is_array($it))->map(fn ($it) => ['a' => $it['a'] ?? '', 'b' => $it['b'] ?? ''])->values()->all(),
        'settings' => is_array($b['settings'] ?? null) ? $b['settings'] : (json_decode($b['settings'] ?? '', true) ?: []),
    ];
    $blocks = old('blocks')
        ? collect(old('blocks'))->values()->map(fn ($b) => $toArray($b))
        : $post->blocks->map(fn ($b) => $toArray([
            'type' => $b->type, 'title' => $b->title, 'body' => $b->body, 'caption' => $b->caption, 'url' => $b->url, 'alt' => $b->alt,
            'size' => $b->type === 'download' ? ($b->items['size'] ?? '') : '', 'existing' => $b->media_path, 'gallery' => $b->media ?? [],
            'items' => in_array($b->type, ['stats', 'faq'], true) ? ($b->items ?? []) : [], 'settings' => $b->settings ?? [],
        ]));
    $s = old('settings', $post->settings ?? []) + Post::cleanSettings([]);
    $meta = [
        'title' => old('title', $post->title ?? ''), 'excerpt' => old('excerpt', $post->excerpt ?? ''),
        'slug' => old('slug', $post->exists ? $post->slug : ''), 'slugEdited' => (bool) old('slug', $post->exists),
        'seoTitle' => old('seo_title', $post->seo_title ?? ''), 'seoDescription' => old('seo_description', $post->seo_description ?? ''),
        'publishAt' => old('publish_at', $post->published_at?->format('Y-m-d\TH:i') ?? ''),
        'isPublished' => $post->exists && $post->status === 'published',
        'type' => old('type', $post->type ?? 'article'), 's' => $s,
    ];
    $coverPath = old('cover_upload');
    $widgets = [
        'Text' => [
            'heading' => ['Heading', 'heading', 'Section title'],
            'text' => ['Text', 'paragraph', 'Rich paragraphs'],
            'list' => ['List', 'list', 'Bullets, steps, checks'],
            'quote' => ['Quote', 'quote', 'Pull quote'],
            'callout' => ['Callout', 'flag', 'Tip, note, warning'],
        ],
        'Media' => [
            'image' => ['Image', 'image', 'One picture'],
            'gallery' => ['Gallery', 'gallery', 'Grid of images'],
            'image_text' => ['Image + text', 'columns', 'Side by side'],
            'embed' => ['Video', 'video', 'YouTube / Vimeo'],
        ],
        'Data & actions' => [
            'stats' => ['Key figures', 'chart', 'Big numbers'],
            'table' => ['Table', 'grid', 'Rows & columns'],
            'faq' => ['FAQ', 'help', 'Questions & answers'],
            'button' => ['Call to action', 'arrow-up-right', 'Heading + button'],
            'download' => ['Download', 'download', 'PDF or file'],
            'spacer' => ['Spacer', 'spacer', 'Space or line'],
        ],
    ];
    $flat = collect($widgets)->collapse();
    $labels = $flat->map(fn ($w) => $w[0]);
    $controls = [
        'Content' => [
            'level' => ['Heading size', ['h2' => 'Section (H2)', 'h3' => 'Sub (H3)']],
            'size' => ['Text size', ['base' => 'Normal', 'lg' => 'Large']],
            'dropcap' => ['Drop cap', ['0' => 'Off', '1' => 'On']],
            'tone' => ['Callout style', ['info' => 'Info', 'tip' => 'Tip', 'warning' => 'Warning', 'success' => 'Success', 'note' => 'Note']],
            'style' => ['List style', ['bullet' => 'Bullets', 'number' => 'Steps', 'check' => 'Checks']],
            'cols' => ['Columns', ['2' => '2', '3' => '3', '4' => '4']],
            'gap' => ['Gap between images', ['none' => 'None', 's' => 'Small', 'm' => 'Large']],
            'side' => ['Image position', ['left' => 'Left', 'right' => 'Right']],
            'header' => ['First row is a header', ['1' => 'Yes', '0' => 'No']],
            'striped' => ['Striped rows', ['1' => 'On', '0' => 'Off']],
            'open' => ['First answer open', ['0' => 'No', '1' => 'Yes']],
            'variant' => ['Button style', ['dark' => 'Dark', 'accent' => 'Accent', 'light' => 'Light']],
            'space' => ['Height', ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large']],
            'line' => ['Divider line', ['0' => 'Off', '1' => 'On']],
        ],
        'Layout' => [
            'align' => ['Alignment', ['left' => 'Left', 'center' => 'Center']],
            'width' => ['Width', ['narrow' => 'Reading', 'wide' => 'Wide', 'full' => 'Edge to edge']],
        ],
        'Style' => [
            'bg' => ['Background', ['none' => 'None', 'light' => 'Light', 'dark' => 'Dark', 'accent' => 'Accent']],
            'pad' => ['Vertical spacing', ['none' => 'None', 's' => 'S', 'm' => 'M', 'l' => 'L']],
        ],
    ];
    $swatch = ['none' => 'bg-white', 'light' => 'bg-zinc-100', 'dark' => 'bg-ink'];
    $seg = 'flex-1 rounded-lg px-2 py-1.5 text-xs font-medium transition';
    $accentSwatch = collect(Post::ACCENTS)->map(fn ($a) => $a['bg']);
    $progress = fn (string $t, string $d) => <<<HTML
        <template x-if="{$t}.up">
            <div class="absolute inset-0 z-[6] grid place-items-center rounded-[inherit] transition" :class="{$t}.up.status === 'error' ? 'bg-rose-950/60' : ({$t}.up.status === 'done' ? 'bg-transparent' : 'bg-black/40')" @click.prevent.stop>
                <div x-show="{$t}.up.status === 'uploading'" class="w-2/3 max-w-[220px] text-center text-white">
                    <p class="text-xs font-semibold drop-shadow" x-text="{$t}.up.stage === 'Uploading' ? 'Uploading ' + {$t}.up.progress + '%' : {$t}.up.stage"></p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/30"><div class="h-full rounded-full bg-white transition-all" :style="'width:' + {$t}.up.progress + '%'"></div></div>
                </div>
                <div x-show="{$t}.up.status === 'done'" class="absolute bottom-2 left-2 inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white shadow"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 5 5L20 7"/></svg><span x-text="{$t}.up.note || 'Done'"></span></div>
                <div x-show="{$t}.up.status === 'error'" class="px-3 text-center text-white">
                    <p class="text-xs font-semibold" x-text="{$t}.up.error"></p>
                    <button type="button" @click.stop="retry({$t}, {$d})" class="mt-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-ink hover:bg-zinc-100">Retry</button>
                </div>
            </div>
        </template>
    HTML;
    // Rich-text toolbar for text-like blocks (keeps the selection with mousedown.prevent).
    $toolbar = <<<'HTML'
        <div x-show="selected === block.key" x-cloak class="mb-2 inline-flex flex-wrap items-center gap-0.5 rounded-lg border border-line bg-white p-0.5 text-ink shadow-sm" @click.stop>
            <button type="button" @mousedown.prevent="format(block, 'bold')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 text-[13px] font-bold hover:bg-zinc-100" title="Bold (**text**)">B</button>
            <button type="button" @mousedown.prevent="format(block, 'italic')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 text-[13px] italic hover:bg-zinc-100" title="Italic (*text*)">I</button>
            <button type="button" @mousedown.prevent="format(block, 'mark')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 text-[13px] hover:bg-zinc-100" title="Highlight (==text==)"><span class="rounded bg-amber-200 px-1">H</span></button>
            <button type="button" @mousedown.prevent="format(block, 'code')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 text-[12px] hover:bg-zinc-100" title="Code (`text`)">&lt;/&gt;</button>
            <span class="mx-0.5 h-5 w-px bg-line"></span>
            <button type="button" @mousedown.prevent="format(block, 'link')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 hover:bg-zinc-100" title="Link"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg></button>
            <button type="button" @mousedown.prevent="format(block, 'bullet')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 hover:bg-zinc-100" title="Bullet list"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/></svg></button>
            <button type="button" @mousedown.prevent="format(block, 'number')" class="grid h-7 min-w-7 place-items-center rounded-md px-1.5 text-[11px] font-semibold hover:bg-zinc-100" title="Numbered list">1.</button>
        </div>
    HTML;
    $state = $post->exists ? $post->state() : 'draft';
@endphp
<x-layouts.admin :title="$post->exists ? 'Edit post' : 'New post'">
<form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
      x-data="postBuilder({ blocks: @js($blocks), defaults: @js(PostBlock::TYPE_SETTINGS), labels: @js($labels), uploadUrl: @js(route('admin.posts.upload')), fileUrl: @js(route('admin.posts.file')),
                            cover: @js($coverPath ? $url($coverPath) : $post->coverUrl()), coverPath: @js($coverPath), meta: @js($meta) })"
      @submit="dirty = false; beforeSubmit($event)" @input="dirty = true" @beforeunload.window="guard($event)"
      @keydown.escape.window="deselect(); picker = null" @dragend.window="endDrag()"
      @keydown.window.ctrl.s.prevent="$refs.quicksave.click()" @keydown.window.meta.s.prevent="$refs.quicksave.click()"
      class="space-y-4">
    @csrf @if($post->exists) @method('PUT') @endif
    <input type="hidden" name="remove_cover" :value="!cover.preview && !cover.path ? 1 : 0">
    <button type="submit" name="intent" value="{{ $post->exists && $post->status === 'published' ? 'save' : 'draft' }}" x-ref="quicksave" class="hidden" tabindex="-1">Save</button>

    {{-- ───────── Top bar ───────── --}}
    <div class="card sticky top-16 z-30 flex flex-wrap items-center gap-3 px-4 py-3 shadow-[0_6px_20px_-12px_rgba(12,12,16,.25)] sm:px-5">
        <a href="{{ route('admin.posts') }}" class="grid size-9 place-items-center rounded-lg hover:bg-zinc-100" aria-label="Back to posts"><x-icon name="chevron-left" class="size-5"/></a>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold" x-text="meta.title || '{{ $post->exists ? 'Untitled post' : 'New post' }}'">{{ $post->title ?: 'New post' }}</p>
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                <x-status :status="$state"/>
                @if($post->isScheduled())<span>Goes live {{ $post->published_at->format('M j, g:ia') }}</span>@endif
                <span x-text="blocks.length + (blocks.length === 1 ? ' block' : ' blocks') + ' · ' + words.toLocaleString() + ' words · ' + readingMinutes + ' min read'"></span>
                <span x-show="dirty" x-cloak class="inline-flex items-center gap-1 text-amber-700"><span class="size-1.5 rounded-full bg-amber-500"></span>Unsaved changes</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span x-show="uploading" x-cloak class="inline-flex items-center gap-2 text-xs font-medium text-muted"><span class="size-3.5 animate-spin rounded-full border-2 border-zinc-300 border-t-ink"></span><span x-text="'Uploading ' + uploading + '…'"></span></span>
            @if($post->exists)
                <a href="{{ route('insights.show', $post) }}" target="_blank" class="btn btn-light h-10" title="Opens the post as readers see it (drafts are visible to admins only)"><x-icon name="eye" class="size-4"/> <span class="hidden sm:inline">{{ $post->isPublic() ? 'View' : 'Preview' }}</span></a>
            @endif
            <button name="intent" value="draft" class="btn btn-light h-10" :disabled="uploading > 0">{{ $post->exists && $post->status === 'published' ? 'Switch to draft' : 'Save draft' }}</button>
            <button name="intent" value="publish" class="btn btn-dark h-10" :disabled="uploading > 0"><x-icon name="arrow-up-right" class="size-4"/> <span x-text="publishLabel">Publish</span></button>
            @if($post->exists)
                <div x-data="{ more: false }" class="relative" @keydown.escape="more = false">
                    <button type="button" @click="more = !more" class="btn btn-light h-10 w-10 px-0" aria-label="More actions"><x-icon name="more" class="size-5"/></button>
                    <div x-show="more" x-cloak @click.outside="more = false" x-transition.origin.top.right class="absolute right-0 top-full z-50 mt-2 w-56 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                        <button type="submit" form="post-duplicate" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="copy" class="size-4 text-muted"/> Duplicate</button>
                        <button type="submit" form="post-feature" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="sparkles" class="size-4 text-muted"/> {{ $post->featured_at ? 'Unpin from top' : 'Pin to top of Insights' }}</button>
                        @if($post->status !== 'archived')
                            <button type="submit" form="post-archive" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="box" class="size-4 text-muted"/> Archive</button>
                        @endif
                        <div class="my-1 h-px bg-line"></div>
                        <button type="submit" form="post-delete" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="trash" class="size-4"/> Delete permanently</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div x-show="notice" x-cloak class="flex items-center justify-between gap-3 rounded-2xl bg-amber-50 px-5 py-3 text-sm text-amber-800"><span x-text="notice"></span><button type="button" @click="notice = ''" aria-label="Dismiss"><x-icon name="x" class="size-4"/></button></div>
    @if($errors->any())
        <div class="rounded-2xl bg-rose-50 px-5 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[330px_minmax(0,1fr)]">

        {{-- ───────── Panel ───────── --}}
        <aside data-fit="scroll" class="card overflow-hidden xl:sticky xl:top-[8.5rem] xl:flex xl:max-h-[calc(100vh-10rem)] xl:flex-col">
            <div class="grid grid-cols-4 gap-1 border-b border-line p-1.5">
                @foreach(['add' => ['Add', 'plus'], 'style' => ['Style', 'settings'], 'post' => ['Post', 'file'], 'seo' => ['SEO', 'search']] as $t => [$tl, $ti])
                    <button type="button" @click="tab = '{{ $t }}'" class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-[13px] font-medium transition" :class="tab === '{{ $t }}' ? 'bg-ink text-white' : 'text-ink-soft hover:bg-zinc-100'"><x-icon :name="$ti" class="size-4"/> {{ $tl }}</button>
                @endforeach
            </div>

            <div class="overflow-y-auto p-4">
                {{-- Add --}}
                <div x-show="tab === 'add'" class="space-y-5">
                    <p class="text-xs text-muted">Drag a block onto the page, or click to add it <span x-text="current ? 'after the selected block' : 'at the end'"></span>.</p>
                    @foreach($widgets as $group => $items)
                        <div>
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-faint">{{ $group }}</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($items as $type => [$wl, $wi, $wd])
                                    <button type="button" draggable="true" @dragstart="startDrag($event, { type: '{{ $type }}' })" @click="insert('{{ $type }}'); {{ in_array($type, ['stats', 'faq'], true) ? "ensureItems(current, ".($type === 'stats' ? 3 : 2).")" : '' }}"
                                            class="group flex cursor-grab items-center gap-2.5 rounded-xl border border-line bg-white px-2.5 py-2.5 text-left transition hover:-translate-y-0.5 hover:border-ink hover:shadow-sm active:cursor-grabbing">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-zinc-100 text-ink-soft transition group-hover:bg-ink group-hover:text-white"><x-icon :name="$wi" class="size-4"/></span>
                                        <span class="min-w-0"><span class="block truncate text-[13px] font-semibold">{{ $wl }}</span><span class="block truncate text-[11px] text-muted">{{ $wd }}</span></span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div class="rounded-xl bg-zinc-50 p3 p-3 text-[11px] leading-relaxed text-muted">
                        <p class="font-semibold text-ink-soft">Formatting in text</p>
                        <p class="mt-1">**bold** · *italic* · ==highlight== · `code` · [link](https://…) · lines starting with “- ” or “1. ” become lists. Select text and use the toolbar.</p>
                        <p class="mt-1">Press <kbd class="rounded border border-line bg-white px-1">Ctrl</kbd> + <kbd class="rounded border border-line bg-white px-1">S</kbd> to save.</p>
                    </div>
                </div>

                {{-- Style --}}
                <div x-show="tab === 'style'" x-cloak>
                    <template x-if="!current">
                        <div class="flex flex-col items-center px-4 py-10 text-center">
                            <span class="grid size-11 place-items-center rounded-xl bg-zinc-100 text-muted"><x-icon name="settings" class="size-5"/></span>
                            <p class="mt-3 text-sm font-medium">No block selected</p>
                            <p class="mt-1 text-xs text-muted">Click any block on the page to change its layout and style.</p>
                        </div>
                    </template>
                    <template x-if="current">
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold" x-text="labels[current.type]"></p>
                                <button type="button" class="text-xs font-medium text-muted hover:text-ink" @click="current.settings = JSON.parse(JSON.stringify(defaults[current.type]))"><x-icon name="refresh" class="inline size-3.5"/> Reset</button>
                            </div>
                            <div x-show="['image', 'image_text'].includes(current.type)" class="space-y-1.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">Alt text</p>
                                <div class="flex gap-3">
                                    <span class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><template x-if="current.preview"><img :src="current.preview" alt="" class="size-full object-cover"></template></span>
                                    <textarea data-alt-field x-model="current.alt" rows="3" maxlength="200" class="input h-auto flex-1 py-2" aria-label="Alt text" placeholder="Describe the image for screen readers"></textarea>
                                </div>
                            </div>
                            <div x-show="current.type === 'gallery'" class="space-y-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">Alt text <span class="font-normal normal-case tracking-normal" x-text="'· ' + (current.gallery.length - missingAlt(current)) + ' of ' + current.gallery.length + ' done'"></span></p>
                                <template x-for="(g, gi) in current.gallery" :key="g.id">
                                    <div class="flex gap-3">
                                        <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><img :src="g.url" alt="" class="size-full object-cover"></span>
                                        <textarea x-model="g.alt" rows="2" maxlength="200" class="input h-auto flex-1 py-1.5 text-[13px]" :aria-label="`Alt text for image ${gi + 1}`" :placeholder="`Describe image ${gi + 1}`"></textarea>
                                    </div>
                                </template>
                            </div>
                            @foreach($controls as $group => $items)
                                <div class="space-y-4" x-show="{{ collect(array_keys($items))->map(fn ($k) => "has('{$k}')")->implode(' || ') }}">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">{{ $group }}</p>
                                    @foreach($items as $key => [$cl, $opts])
                                        <div x-show="has('{{ $key }}')">
                                            <p class="mb-1.5 text-xs font-medium text-ink-soft">{{ $cl }}</p>
                                            @if($key === 'bg')
                                                <div class="grid grid-cols-4 gap-2">
                                                    @foreach($opts as $v => $ol)
                                                        <button type="button" @click="set('bg', '{{ $v }}')" class="flex flex-col items-center gap-1 rounded-xl border p-2 text-[11px] font-medium transition" :class="current.settings.bg === '{{ $v }}' ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-zinc-300'">
                                                            @if($v === 'accent')<span class="size-6 rounded-full border border-line" :class="wrap({ settings: { bg: 'accent', pad: 'none' } })"></span>@else<span class="size-6 rounded-full border border-line {{ $swatch[$v] }}"></span>@endif{{ $ol }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @elseif($key === 'tone')
                                                <div class="grid grid-cols-5 gap-1.5">
                                                    @foreach(PostBlock::TONES as $v => [$tl, $ti, $tc])
                                                        <button type="button" @click="set('tone', '{{ $v }}')" class="flex flex-col items-center gap-1 rounded-xl border p-1.5 text-[10px] font-medium transition {{ $tc }}" :class="current.settings.tone === '{{ $v }}' ? 'ring-2 ring-ink ring-offset-1' : ''" title="{{ $tl }}"><x-icon :name="$ti" class="size-4"/>{{ $opts[$v] }}</button>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="flex gap-1 rounded-xl bg-zinc-100 p-1">
                                                    @foreach($opts as $v => $ol)
                                                        <button type="button" @click="set('{{ $key }}', '{{ $v }}')" class="{{ $seg }}" :class="current.settings['{{ $key }}'] === '{{ $v }}' ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'">
                                                            @if($key === 'align')<x-icon :name="'align-'.$v" class="mx-auto size-4"/><span class="sr-only">{{ $ol }}</span>@else{{ $ol }}@endif
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </template>
                </div>

                {{-- Post settings --}}
                <div x-show="tab === 'post'" x-cloak class="space-y-6">
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-faint">Type</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(Post::TYPES as $v => [$tl, $tp, $ti])
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-2.5 py-2 text-[13px] font-medium transition" :class="meta.type === '{{ $v }}' ? 'border-ink bg-zinc-50 ring-1 ring-ink' : 'border-line hover:border-zinc-300'">
                                    <input type="radio" name="type" value="{{ $v }}" x-model="meta.type" class="sr-only"><x-icon :name="$ti" class="size-4 text-ink-soft"/> {{ $tl }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-faint">Written for</p>
                        <div class="flex gap-1 rounded-xl bg-zinc-100 p-1" x-data="{ aud: @js(old('audience', $post->audience ?? 'everyone')) }">
                            @foreach(Post::AUDIENCES as $v => $al)
                                <label class="{{ $seg }} cursor-pointer text-center" :class="aud === '{{ $v }}' ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"><input type="radio" name="audience" value="{{ $v }}" x-model="aud" class="sr-only">{{ ucfirst(str_replace('For ', '', $al)) }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div><label class="label" for="topics">Topics</label><input id="topics" name="topics" value="{{ old('topics', implode(', ', $post->topics ?? [])) }}" maxlength="300" class="input" placeholder="Pricing, Branding, Ethiopia"><p class="hint">Comma separated · up to 8</p></div>
                    <div><label class="label" for="author_label">Byline</label><input id="author_label" name="author_label" value="{{ old('author_label', $post->author_label) }}" maxlength="120" class="input" placeholder="{{ ($post->author ?? auth()->user())->name }}"><p class="hint">Leave empty to show {{ ($post->author ?? auth()->user())->name }}, or write e.g. “{{ config('marketplace.brand') }} Research”.</p></div>

                    <div class="space-y-3 rounded-2xl border border-line p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">Publishing</p>
                        <div><label class="label" for="publish_at">Publish date & time</label><input id="publish_at" type="datetime-local" name="publish_at" x-model="meta.publishAt" class="input"><p class="hint" x-text="scheduled ? 'In the future — the post will be scheduled and go live then.' : 'Leave empty to publish right away. A future time schedules it.'"></p></div>
                        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="featured" value="0"><input type="checkbox" name="featured" value="1" class="checkbox" @checked(old('featured', (bool) $post->featured_at))> Pin to the top of Insights</label>
                    </div>

                    <div class="space-y-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">Design</p>
                        <div>
                            <p class="mb-1.5 text-xs font-medium text-ink-soft">Page layout</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['article' => ['Article', 'Contents sidebar'], 'feature' => ['Feature', 'Big hero'], 'minimal' => ['Minimal', 'Just text']] as $v => [$ll, $ld])
                                    <label class="cursor-pointer rounded-xl border p-2 text-center transition" :class="meta.s.layout === '{{ $v }}' ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-zinc-300'">
                                        <input type="radio" name="settings[layout]" value="{{ $v }}" x-model="meta.s.layout" class="sr-only">
                                        <span class="mx-auto mb-1.5 flex h-10 w-full gap-1 rounded-md bg-zinc-50 p-1">
                                            @if($v === 'article')<span class="flex-1 space-y-0.5"><span class="block h-1.5 rounded bg-zinc-300"></span><span class="block h-1 rounded bg-zinc-200"></span><span class="block h-1 rounded bg-zinc-200"></span></span><span class="w-2 rounded bg-zinc-200"></span>
                                            @elseif($v === 'feature')<span class="flex-1 rounded bg-zinc-300"></span>
                                            @else<span class="mx-auto w-2/3 space-y-0.5 pt-1"><span class="block h-1 rounded bg-zinc-300"></span><span class="block h-1 rounded bg-zinc-200"></span><span class="block h-1 rounded bg-zinc-200"></span></span>@endif
                                        </span>
                                        <span class="block text-[12px] font-semibold">{{ $ll }}</span><span class="block text-[10px] text-muted">{{ $ld }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="mb-1.5 text-xs font-medium text-ink-soft">Cover image</p>
                            <div class="flex gap-1 rounded-xl bg-zinc-100 p-1">
                                @foreach(['hero' => 'Large', 'contained' => 'In column', 'none' => 'Hidden'] as $v => $cl)
                                    <label class="{{ $seg }} cursor-pointer text-center" :class="meta.s.cover === '{{ $v }}' ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"><input type="radio" name="settings[cover]" value="{{ $v }}" x-model="meta.s.cover" class="sr-only">{{ $cl }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="mb-1.5 text-xs font-medium text-ink-soft">Accent colour</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($accentSwatch as $v => $cls)
                                    <label class="cursor-pointer" title="{{ ucfirst($v) }}"><input type="radio" name="settings[accent]" value="{{ $v }}" x-model="meta.s.accent" class="peer sr-only"><span class="block size-8 rounded-full ring-2 ring-offset-2 transition {{ $cls }}" :class="meta.s.accent === '{{ $v }}' ? 'ring-ink' : 'ring-transparent hover:ring-zinc-300'"></span></label>
                                @endforeach
                            </div>
                            <p class="hint">Used for the label, links, lists, figures and highlights.</p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-xs font-medium text-ink-soft">Show on the page</p>
                            @foreach(['toc' => 'Table of contents (from H2/H3 headings)', 'reading_time' => 'Reading time', 'author_box' => 'Author card at the end', 'share' => 'Share buttons', 'related' => 'Related posts'] as $k => $tl)
                                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="settings[{{ $k }}]" value="0"><input type="checkbox" name="settings[{{ $k }}]" value="1" class="checkbox" @checked(($s[$k] ?? '1') === '1')> {{ $tl }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div x-show="tab === 'seo'" x-cloak class="space-y-5">
                    <div>
                        <label class="label" for="slug">Web address</label>
                        <div class="flex items-center rounded-xl border border-line bg-white focus-within:border-ink">
                            <span class="shrink-0 pl-3 text-sm text-muted">/insights/</span>
                            <input id="slug" name="slug" x-model="meta.slug" @input="meta.slugEdited = true; meta.slug = slugify(meta.slug)" maxlength="120" class="h-11 min-w-0 flex-1 rounded-xl border-0 bg-transparent pl-0.5 pr-3 text-sm focus:outline-none focus:ring-0" :placeholder="slugPreview">
                        </div>
                        <p class="hint">@if($post->isPublic())Changing it breaks links already shared.@else Made from the title unless you set one.@endif</p>
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between"><label class="label" for="seo_title">Search title</label><span class="text-[11px] tabular-nums" :class="(meta.seoTitle || meta.title).length > 60 ? 'text-amber-700' : 'text-muted'" x-text="(meta.seoTitle || meta.title).length + ' / 60'"></span></div>
                        <input id="seo_title" name="seo_title" x-model="meta.seoTitle" maxlength="70" class="input" :placeholder="meta.title || 'Defaults to the post title'">
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between"><label class="label" for="seo_description">Search description</label><span class="text-[11px] tabular-nums" :class="seoDescription.length > 160 ? 'text-amber-700' : 'text-muted'" x-text="(meta.seoDescription || '').length + ' / 160'"></span></div>
                        <textarea id="seo_description" name="seo_description" x-model="meta.seoDescription" rows="3" maxlength="180" class="input h-auto py-2" placeholder="Defaults to the excerpt"></textarea>
                    </div>
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-faint">Search result preview</p>
                        <div class="rounded-xl border border-line bg-white p-3">
                            <p class="truncate text-[12px] text-ink-soft">{{ parse_url(config('marketplace.public_url') ?: config('app.url'), PHP_URL_HOST) ?: 'dezignershub' }} › insights › <span x-text="slugPreview"></span></p>
                            <p class="mt-0.5 line-clamp-1 text-[15px] font-medium text-[#1a0dab]" x-text="seoTitle + ' | {{ config('marketplace.brand') }}'"></p>
                            <p class="mt-0.5 line-clamp-2 text-[12px] leading-snug text-ink-soft" x-text="seoDescription"></p>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-faint">Social card preview</p>
                        <div class="overflow-hidden rounded-xl border border-line bg-white">
                            <div class="aspect-[1.91/1] bg-zinc-100"><template x-if="cover.preview"><img :src="cover.preview" alt="" class="size-full object-cover"></template></div>
                            <div class="p-3"><p class="line-clamp-1 text-[13px] font-semibold" x-text="seoTitle"></p><p class="line-clamp-1 text-[12px] text-muted" x-text="seoDescription"></p></div>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-faint">Contents <span class="font-normal normal-case tracking-normal">· from your headings</span></p>
                        <ol class="space-y-1 text-sm">
                            <template x-for="h in outline" :key="h.key"><li :class="h.level === 'h3' && 'pl-4 text-muted'"><button type="button" class="text-left hover:underline" @click="document.getElementById('block-' + h.key)?.scrollIntoView({ behavior: 'smooth', block: 'center' })" x-text="h.text"></button></li></template>
                        </ol>
                        <p x-show="!outline.length" class="text-xs text-muted">Add Heading blocks to build the table of contents.</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ───────── Canvas ───────── --}}
        <div class="card overflow-hidden" @click.self="deselect()">
            {{-- Post header --}}
            <div class="bg-gradient-to-b px-6 pb-8 pt-10 sm:px-10" :class="{ blue: 'from-[#eaf2fc]', violet: 'from-[#f1ebfc]', green: 'from-[#e6f4ed]', amber: 'from-[#fdf3e1]', rose: 'from-[#fcebf0]', ink: 'from-zinc-100' }[meta.s.accent] + ' to-white'" @click="deselect()">
                <div class="mx-auto max-w-3xl">
                    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em]" :class="accentText()">
                        <span x-text="@js(collect(Post::TYPES)->map(fn ($t) => $t[0]))[meta.type]"></span>
                        <span class="text-faint">·</span><span class="font-medium normal-case tracking-normal text-muted" x-text="readingMinutes + ' min read'"></span>
                    </p>
                    <label class="sr-only" for="title">Title</label>
                    <textarea id="title" name="title" rows="1" required maxlength="180" x-model="meta.title" x-init="$nextTick(() => grow($el))" @input="grow($el)" class="mt-3 block w-full resize-none border-0 bg-transparent p-0 font-display text-4xl font-bold leading-[1.05] tracking-tight placeholder:text-zinc-300 focus:outline-none focus:ring-0 sm:text-5xl" placeholder="Post title"></textarea>
                    <x-field-error name="title"/>
                    <label class="sr-only" for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="1" maxlength="400" x-model="meta.excerpt" x-init="$nextTick(() => grow($el))" @input="grow($el)" class="mt-4 block w-full resize-none border-0 bg-transparent p-0 text-lg leading-relaxed text-ink-soft placeholder:text-zinc-300 focus:outline-none focus:ring-0 sm:text-xl" placeholder="A one- or two-sentence summary — shown under the title, on cards and in search results…"></textarea>
                </div>
            </div>

            {{-- Cover --}}
            <div @click="deselect()" class="px-6 sm:px-10">
                <input type="hidden" name="cover_upload" :value="cover.path || ''">
                <div class="mx-auto max-w-5xl">
                    <label class="group relative block cursor-pointer">
                        <template x-if="cover.preview"><img :src="cover.preview" alt="" class="block aspect-[16/8] w-full rounded-2xl object-cover"></template>
                        <div x-show="!cover.preview" class="grid aspect-[21/8] place-items-center rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 text-center text-muted transition group-hover:border-ink">
                            <span><x-icon name="upload" class="mx-auto size-7"/><span class="mt-2 block text-sm font-medium text-ink">Add a cover image</span><span class="text-xs">Used at the top of the post, on cards and when shared · 1600×900 or larger</span></span>
                        </div>
                        <span x-show="cover.preview && !cover.up" class="absolute right-4 top-4 z-[7] inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium opacity-0 shadow backdrop-blur transition group-hover:opacity-100"><x-icon name="image" class="size-4"/> Replace cover</span>
                        {!! $progress('cover', 'coverDone()') !!}
                        <input type="file" accept="image/*" class="sr-only" @change="pickCover($event)">
                    </label>
                    <div x-show="cover.preview" class="mt-2 grid gap-1 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                        <input name="cover_alt" value="{{ old('cover_alt', $post->cover_alt) }}" maxlength="200" class="block w-full border-0 bg-transparent py-1 text-sm text-ink-soft placeholder:text-zinc-400 focus:outline-none focus:ring-0" placeholder="Alt text: describe the cover for screen readers">
                        <input name="cover_caption" value="{{ old('cover_caption', $post->cover_caption) }}" maxlength="200" class="block w-full border-0 bg-transparent py-1 text-sm text-ink-soft placeholder:text-zinc-400 focus:outline-none focus:ring-0" placeholder="Caption or credit (optional)">
                        <button type="button" @click="cover = { preview: null, path: null, up: null }; dirty = true" class="justify-self-start text-xs font-medium text-rose-600 hover:underline">Remove cover</button>
                    </div>
                </div>
            </div>

            {{-- Blocks --}}
            <div class="relative mt-6 min-h-[200px] pb-6" @dragover.prevent="if (drag && !blocks.length) dropAt = 0" @drop.prevent="drop()">
                <template x-for="(block, i) in blocks" :key="block.key">
                    <div>
                        <div x-show="drag && dropAt === i" class="mx-6 my-1 h-1 rounded-full bg-brand"></div>
                        <div :id="`block-${block.key}`" class="group/b relative" @click.stop="select(block)" @dragover.prevent.stop="over($event, i)" @drop.prevent.stop="drop()" :class="drag && drag.from === i && 'opacity-40'">
                            <div class="pointer-events-none absolute inset-0 z-10 transition" :class="selected === block.key ? 'ring-2 ring-inset ring-brand' : 'group-hover/b:ring-1 group-hover/b:ring-inset group-hover/b:ring-brand/40'"></div>
                            <span class="absolute left-3 top-3 z-20 rounded-md bg-brand px-2 py-0.5 text-[11px] font-semibold text-white" x-show="selected === block.key" x-text="labels[block.type]"></span>
                            <button type="button" x-show="missingAlt(block)" x-cloak @click.stop="editAlt(block)" class="absolute bottom-3 left-3 z-20 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800 shadow-sm hover:bg-amber-200"><x-icon name="pen" class="size-3"/> <span x-text="block.type === 'gallery' ? missingAlt(block) + ' missing alt text' : 'Add alt text'"></span></button>
                            <div class="absolute right-3 top-3 z-20 flex items-center gap-0.5 rounded-lg border border-line bg-white p-0.5 shadow-md transition" :class="selected === block.key ? 'opacity-100' : 'pointer-events-none opacity-0 group-hover/b:pointer-events-auto group-hover/b:opacity-100'">
                                <span draggable="true" @dragstart.stop="startDrag($event, { from: i })" class="grid size-7 cursor-grab place-items-center rounded-md text-muted hover:bg-zinc-100 active:cursor-grabbing" title="Drag to reorder"><x-icon name="grip" class="size-4"/></span>
                                <button type="button" @click.stop="move(i, -1)" :disabled="i === 0" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100 disabled:opacity-30" title="Move up"><x-icon name="chevron-down" class="size-4 rotate-180"/></button>
                                <button type="button" @click.stop="move(i, 1)" :disabled="i === blocks.length - 1" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100 disabled:opacity-30" title="Move down"><x-icon name="chevron-down" class="size-4"/></button>
                                <button type="button" @click.stop="duplicate(i)" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100" title="Duplicate"><x-icon name="copy" class="size-4"/></button>
                                <button type="button" @click.stop="select(block); tab = 'style'" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100" title="Style"><x-icon name="settings" class="size-4"/></button>
                                <button type="button" @click.stop="remove(i)" class="grid size-7 place-items-center rounded-md text-rose-600 hover:bg-rose-50" title="Delete"><x-icon name="trash" class="size-4"/></button>
                            </div>

                            {{-- data not edited directly on the canvas --}}
                            <input type="hidden" :name="`blocks[${block.key}][type]`" :value="block.type">
                            <input type="hidden" :name="`blocks[${block.key}][settings]`" :value="JSON.stringify(block.settings)">
                            <template x-if="['image', 'image_text', 'download'].includes(block.type)"><span>
                                <input type="hidden" :name="`blocks[${block.key}][existing]`" :value="block.existing || ''">
                                <input type="hidden" :name="`blocks[${block.key}][alt]`" :value="block.alt || ''">
                                <input type="hidden" :name="`blocks[${block.key}][size]`" :value="block.size || ''">
                            </span></template>
                            <template x-for="(g, gi) in block.gallery.filter((x) => x.path)" :key="'h' + g.id">
                                <span><input type="hidden" :name="`blocks[${block.key}][gallery][${gi}][path]`" :value="g.path"><input type="hidden" :name="`blocks[${block.key}][gallery][${gi}][alt]`" :value="g.alt || ''"></span>
                            </template>

                            <div :class="wrap(block)">
                                <div :class="inner(block)">
                                    {{-- Heading --}}
                                    <template x-if="block.type === 'heading'">
                                        <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="1" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="300"
                                                  class="block w-full resize-none border-0 bg-transparent p-0 font-display font-bold leading-tight tracking-tight placeholder:opacity-30 focus:outline-none focus:ring-0"
                                                  :class="[block.settings.level === 'h3' ? 'text-xl sm:text-2xl' : 'text-[26px] sm:text-[32px]', block.settings.align === 'center' && 'text-center']" placeholder="Section heading"></textarea>
                                    </template>
                                    {{-- Text --}}
                                    <template x-if="block.type === 'text'">
                                        <div>
                                            {!! $toolbar !!}
                                            <textarea data-rich :name="`blocks[${block.key}][body]`" x-model="block.body" rows="3" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="20000"
                                                      class="block w-full resize-none border-0 bg-transparent p-0 leading-relaxed placeholder:opacity-40 focus:outline-none focus:ring-0"
                                                      :class="[block.settings.size === 'lg' ? 'text-xl' : 'text-[17px]', block.settings.bg === 'dark' ? 'text-white/80' : 'text-ink-soft', block.settings.align === 'center' && 'text-center']" placeholder="Write… Select text and use the toolbar for bold, links and lists. Leave an empty line between paragraphs."></textarea>
                                        </div>
                                    </template>
                                    {{-- List --}}
                                    <template x-if="block.type === 'list'">
                                        <div>
                                            <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="mb-2 block w-full border-0 bg-transparent p-0 font-display text-lg font-bold placeholder:opacity-30 focus:outline-none focus:ring-0" placeholder="List title (optional)">
                                            <div class="flex gap-3">
                                                <div class="flex shrink-0 flex-col gap-[11px] pt-[7px]">
                                                    <template x-for="(it, li) in (listItems(block).length ? listItems(block) : [''])" :key="li">
                                                        <span class="grid size-5 place-items-center rounded-full text-[10px] font-bold text-white" :class="accentBg()" x-text="block.settings.style === 'number' ? li + 1 : (block.settings.style === 'check' ? '✓' : '•')"></span>
                                                    </template>
                                                </div>
                                                <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="3" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="8000" class="block w-full resize-none border-0 bg-transparent p-0 text-[17px] leading-[2] text-ink-soft placeholder:opacity-40 focus:outline-none focus:ring-0" placeholder="One item per line&#10;Second item&#10;Third item"></textarea>
                                            </div>
                                        </div>
                                    </template>
                                    {{-- Image --}}
                                    <template x-if="block.type === 'image'">
                                        <figure>
                                            <label class="relative block cursor-pointer rounded-2xl">
                                                <template x-if="block.preview"><img :src="block.preview" alt="" class="block h-auto w-full rounded-2xl"></template>
                                                <div x-show="!block.preview" class="grid aspect-[16/9] place-items-center rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 text-center text-muted hover:border-ink"><span><x-icon name="upload" class="mx-auto size-6"/><span class="mt-1 block text-sm font-medium text-ink">Upload an image</span></span></div>
                                                {!! $progress('block', 'imageDone(block)') !!}
                                                <input type="file" accept="image/*" class="sr-only" @change="pickImage(block, $event)">
                                            </label>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="300" class="mt-2 block w-full border-0 bg-transparent py-1 text-center text-sm text-muted placeholder:text-zinc-300 focus:outline-none focus:ring-0" placeholder="Caption or source (optional)">
                                        </figure>
                                    </template>
                                    {{-- Gallery --}}
                                    <template x-if="block.type === 'gallery'">
                                        <figure>
                                            <div class="grid" :class="[{ '2': 'grid-cols-2', '3': 'grid-cols-3', '4': 'grid-cols-4' }[block.settings.cols], { none: 'gap-0', s: 'gap-2', m: 'gap-4' }[block.settings.gap]]">
                                                <template x-for="(g, gi) in block.gallery" :key="'g' + g.id">
                                                    <div class="group/i relative aspect-[4/3] overflow-hidden bg-zinc-100" :class="block.settings.gap !== 'none' && 'rounded-xl'">
                                                        <img :src="g.url" alt="" class="size-full object-cover">
                                                        {!! $progress('g', 'galleryDone(g)') !!}
                                                        <button type="button" @click.stop="block.gallery.splice(gi, 1)" class="absolute right-2 top-2 z-[7] grid size-7 place-items-center rounded-full bg-black/60 text-white opacity-0 transition group-hover/i:opacity-100" aria-label="Remove image"><x-icon name="x" class="size-4"/></button>
                                                    </div>
                                                </template>
                                                <label x-show="block.gallery.length < 12" class="grid aspect-[4/3] cursor-pointer place-items-center rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 text-center text-muted hover:border-ink">
                                                    <span><x-icon name="plus" class="mx-auto size-6"/><span class="mt-1 block text-xs font-medium text-ink">Add images</span><span class="text-[11px]" x-text="block.gallery.length + ' / 12'"></span></span>
                                                    <input type="file" accept="image/*" multiple class="sr-only" @change="addGallery(block, $event)">
                                                </label>
                                            </div>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="300" class="mt-2 block w-full border-0 bg-transparent py-1 text-center text-sm text-muted placeholder:text-zinc-300 focus:outline-none focus:ring-0" placeholder="Caption (optional)">
                                        </figure>
                                    </template>
                                    {{-- Image + text --}}
                                    <template x-if="block.type === 'image_text'">
                                        <div class="grid items-center gap-6 sm:grid-cols-2">
                                            <label class="relative block cursor-pointer rounded-2xl" :class="block.settings.side === 'right' && 'sm:order-2'">
                                                <template x-if="block.preview"><img :src="block.preview" alt="" class="block h-auto w-full rounded-2xl"></template>
                                                <div x-show="!block.preview" class="grid aspect-[4/3] place-items-center rounded-2xl border-2 border-dashed border-zinc-300 bg-white/60 text-center text-muted hover:border-ink"><span><x-icon name="upload" class="mx-auto size-6"/><span class="mt-1 block text-sm font-medium text-ink">Upload an image</span></span></div>
                                                {!! $progress('block', 'imageDone(block)') !!}
                                                <input type="file" accept="image/*" class="sr-only" @change="pickImage(block, $event)">
                                            </label>
                                            <div>
                                                <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="block w-full border-0 bg-transparent p-0 font-display text-2xl font-bold placeholder:opacity-30 focus:outline-none focus:ring-0" placeholder="Title (optional)">
                                                {!! $toolbar !!}
                                                <textarea data-rich :name="`blocks[${block.key}][body]`" x-model="block.body" rows="4" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="20000" class="mt-2 block w-full resize-none border-0 bg-transparent p-0 text-[17px] leading-relaxed placeholder:opacity-40 focus:outline-none focus:ring-0" :class="block.settings.bg === 'dark' ? 'text-white/80' : 'text-ink-soft'" placeholder="Describe what the image shows…"></textarea>
                                            </div>
                                        </div>
                                    </template>
                                    {{-- Quote --}}
                                    <template x-if="block.type === 'quote'">
                                        <figure :class="block.settings.align !== 'center' && 'border-l-[3px] pl-6'" :style="block.settings.align !== 'center' ? 'border-color: currentColor' : ''">
                                            <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="2" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="1000" class="block w-full resize-none border-0 bg-transparent p-0 font-display text-[22px] font-semibold leading-snug tracking-tight placeholder:opacity-30 focus:outline-none focus:ring-0 sm:text-[28px]" :class="block.settings.align === 'center' && 'text-center'" placeholder="“A sentence worth pulling out…”"></textarea>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="300" class="mt-3 block w-full border-0 bg-transparent p-0 text-sm font-medium text-muted placeholder:opacity-40 focus:outline-none focus:ring-0" :class="block.settings.align === 'center' && 'text-center'" placeholder="— Name, role">
                                        </figure>
                                    </template>
                                    {{-- Video --}}
                                    <template x-if="block.type === 'embed'">
                                        <figure>
                                            <div class="aspect-video w-full overflow-hidden rounded-2xl bg-zinc-900">
                                                <template x-if="embedSrc(block.url)"><iframe :src="embedSrc(block.url)" class="pointer-events-none size-full" title="Video preview"></iframe></template>
                                                <div x-show="!embedSrc(block.url)" class="grid size-full place-items-center text-center text-white/60"><span><x-icon name="video" class="mx-auto size-8"/><span class="mt-2 block text-sm">Paste a YouTube or Vimeo link below</span></span></div>
                                            </div>
                                            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                                <input :name="`blocks[${block.key}][url]`" x-model="block.url" type="url" class="input h-10 flex-1" placeholder="https://www.youtube.com/watch?v=…">
                                                <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="300" class="input h-10 flex-1" placeholder="Caption (optional)">
                                            </div>
                                        </figure>
                                    </template>
                                    {{-- Callout --}}
                                    <template x-if="block.type === 'callout'">
                                        <div class="flex gap-4 rounded-2xl border p-5" :class="@js(collect(PostBlock::TONES)->map(fn ($t) => $t[2]))[block.settings.tone]">
                                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white/80"><x-icon name="flag" class="size-[18px]"/></span>
                                            <div class="min-w-0 flex-1">
                                                <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="block w-full border-0 bg-transparent p-0 font-semibold placeholder:opacity-50 focus:outline-none focus:ring-0" :placeholder="@js(collect(PostBlock::TONES)->map(fn ($t) => $t[0]))[block.settings.tone] + ' — title (optional)'">
                                                {!! $toolbar !!}
                                                <textarea data-rich :name="`blocks[${block.key}][body]`" x-model="block.body" rows="2" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="5000" class="mt-1 block w-full resize-none border-0 bg-transparent p-0 text-[15px] leading-relaxed placeholder:opacity-50 focus:outline-none focus:ring-0" placeholder="Something readers shouldn't miss…"></textarea>
                                            </div>
                                        </div>
                                    </template>
                                    {{-- Key figures --}}
                                    <template x-if="block.type === 'stats'">
                                        <div>
                                            <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="mb-4 block w-full border-0 bg-transparent p-0 text-[13px] font-semibold uppercase tracking-[0.14em] placeholder:opacity-40 focus:outline-none focus:ring-0" placeholder="Section label (optional), e.g. Survey highlights">
                                            <div class="grid gap-3" :class="{ '2': 'sm:grid-cols-2', '3': 'sm:grid-cols-3', '4': 'sm:grid-cols-2 lg:grid-cols-4' }[block.settings.cols]">
                                                <template x-for="(it, ii) in block.items" :key="it.id">
                                                    <div data-item class="group/it relative rounded-2xl bg-white p-5 ring-1 ring-line" :class="block.settings.bg === 'dark' && 'bg-white/5 ring-white/10'">
                                                        <input :name="`blocks[${block.key}][items][${ii}][a]`" x-model="it.a" maxlength="40" class="block w-full border-0 bg-transparent p-0 font-display text-4xl font-bold tracking-tight placeholder:opacity-25 focus:outline-none focus:ring-0" :class="block.settings.bg === 'dark' ? 'text-white' : accentText()" placeholder="68%">
                                                        <input :name="`blocks[${block.key}][items][${ii}][b]`" x-model="it.b" maxlength="200" class="mt-1 block w-full border-0 bg-transparent p-0 text-sm text-muted placeholder:opacity-50 focus:outline-none focus:ring-0" placeholder="What the number means">
                                                        <button type="button" @click.stop="removeItem(block, ii)" class="absolute right-2 top-2 grid size-6 place-items-center rounded-full text-muted opacity-0 hover:bg-zinc-100 hover:text-rose-600 group-hover/it:opacity-100" aria-label="Remove figure"><x-icon name="x" class="size-3.5"/></button>
                                                    </div>
                                                </template>
                                                <button type="button" @click.stop="addItem(block)" x-show="block.items.length < 8" class="grid min-h-[104px] place-items-center rounded-2xl border-2 border-dashed border-zinc-300 text-sm font-medium text-muted hover:border-ink hover:text-ink"><span><x-icon name="plus" class="mx-auto size-5"/> Add figure</span></button>
                                            </div>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="300" class="mt-3 block w-full border-0 bg-transparent p-0 text-xs text-muted placeholder:opacity-50 focus:outline-none focus:ring-0" placeholder="Source or note (optional), e.g. Survey of 412 freelancers, Oct 2026">
                                        </div>
                                    </template>
                                    {{-- Table --}}
                                    <template x-if="block.type === 'table'">
                                        <div>
                                            <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="mb-2 block w-full border-0 bg-transparent p-0 font-display text-lg font-bold placeholder:opacity-30 focus:outline-none focus:ring-0" placeholder="Table title (optional)">
                                            <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="4" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="20000" class="input h-auto py-2 text-sm leading-relaxed" placeholder="One row per line, columns separated by |&#10;Service | Typical price | Turnaround&#10;Logo | 8,000 ETB | 1 week"></textarea>
                                            <div x-show="tableRows(block).length" class="mt-3 overflow-x-auto rounded-xl ring-1 ring-line">
                                                <table class="w-full text-left text-sm">
                                                    <template x-for="(row, ri) in tableRows(block)" :key="ri">
                                                        <tr :class="ri === 0 && block.settings.header === '1' ? 'bg-zinc-50 font-semibold text-ink' : (block.settings.striped === '1' && ri % 2 === 0 ? 'bg-zinc-50/60' : 'bg-white')" class="border-b border-line last:border-0">
                                                            <template x-for="(c, ci) in row" :key="ci"><td class="px-3 py-2" x-text="c"></td></template>
                                                        </tr>
                                                    </template>
                                                </table>
                                            </div>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="300" class="mt-2 block w-full border-0 bg-transparent p-0 text-xs text-muted placeholder:opacity-50 focus:outline-none focus:ring-0" placeholder="Source or note (optional)">
                                        </div>
                                    </template>
                                    {{-- FAQ --}}
                                    <template x-if="block.type === 'faq'">
                                        <div>
                                            <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="mb-3 block w-full border-0 bg-transparent p-0 font-display text-2xl font-bold placeholder:opacity-30 focus:outline-none focus:ring-0" placeholder="Frequently asked questions">
                                            <div class="divide-y divide-line border-y border-line">
                                                <template x-for="(it, ii) in block.items" :key="it.id">
                                                    <div data-item class="group/it relative py-3">
                                                        <input :name="`blocks[${block.key}][items][${ii}][a]`" x-model="it.a" maxlength="200" class="block w-full border-0 bg-transparent p-0 pr-8 text-[17px] font-semibold placeholder:opacity-40 focus:outline-none focus:ring-0" placeholder="Question">
                                                        <textarea :name="`blocks[${block.key}][items][${ii}][b]`" x-model="it.b" rows="2" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="2000" class="mt-1 block w-full resize-none border-0 bg-transparent p-0 leading-relaxed text-ink-soft placeholder:opacity-40 focus:outline-none focus:ring-0" placeholder="Answer"></textarea>
                                                        <button type="button" @click.stop="removeItem(block, ii)" class="absolute right-0 top-3 grid size-6 place-items-center rounded-full text-muted opacity-0 hover:bg-zinc-100 hover:text-rose-600 group-hover/it:opacity-100" aria-label="Remove question"><x-icon name="x" class="size-3.5"/></button>
                                                    </div>
                                                </template>
                                            </div>
                                            <button type="button" @click.stop="addItem(block)" class="btn btn-light btn-sm mt-3"><x-icon name="plus" class="size-4"/> Add question</button>
                                        </div>
                                    </template>
                                    {{-- Call to action --}}
                                    <template x-if="block.type === 'button'">
                                        <div :class="block.settings.align === 'center' && 'text-center'">
                                            <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="block w-full border-0 bg-transparent p-0 font-display text-2xl font-bold placeholder:opacity-30 focus:outline-none focus:ring-0 sm:text-3xl" :class="block.settings.align === 'center' && 'text-center'" placeholder="Heading, e.g. Ready to hire?">
                                            <input :name="`blocks[${block.key}][body]`" x-model="block.body" maxlength="400" class="mt-2 block w-full border-0 bg-transparent p-0 placeholder:opacity-40 focus:outline-none focus:ring-0" :class="[block.settings.align === 'center' && 'text-center', block.settings.bg === 'dark' ? 'text-white/75' : 'text-ink-soft']" placeholder="A short line of supporting text (optional)">
                                            <div class="mt-5 inline-flex flex-wrap items-center gap-2" :class="block.settings.align === 'center' && 'justify-center'">
                                                <span class="inline-flex h-11 items-center gap-2 rounded-full px-5 text-sm font-semibold" :class="{ dark: 'bg-ink text-white', accent: accentBg() + ' text-white', light: 'border border-line bg-white text-ink' }[block.settings.variant]">
                                                    <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="60" class="w-36 border-0 bg-transparent p-0 text-sm font-semibold placeholder:opacity-60 focus:outline-none focus:ring-0" :class="block.settings.variant === 'light' ? 'text-ink' : 'text-white placeholder:text-white'" placeholder="Button label"><x-icon name="arrow-right" class="size-4"/>
                                                </span>
                                                <input :name="`blocks[${block.key}][url]`" x-model="block.url" maxlength="500" class="input h-10 w-64 text-sm" placeholder="https://… or /jobs">
                                            </div>
                                        </div>
                                    </template>
                                    {{-- Download --}}
                                    <template x-if="block.type === 'download'">
                                        <div class="flex items-center gap-4 rounded-2xl border border-line bg-white p-4">
                                            <label class="relative grid size-12 shrink-0 cursor-pointer place-items-center rounded-xl text-[11px] font-bold text-white" :class="block.existing ? accentBg() : 'border-2 border-dashed border-zinc-300 bg-zinc-50 text-muted'" title="Choose a file">
                                                <span x-text="block.existing ? (block.existing.split('.').pop() || 'FILE').toUpperCase() : '+'"></span>
                                                <input type="file" class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.txt" @change="pickFile(block, $event)">
                                            </label>
                                            <div class="min-w-0 flex-1">
                                                <input :name="`blocks[${block.key}][title]`" x-model="block.title" maxlength="300" class="block w-full border-0 bg-transparent p-0 font-semibold placeholder:opacity-50 focus:outline-none focus:ring-0" placeholder="File title, e.g. Full report (PDF)">
                                                <input :name="`blocks[${block.key}][body]`" x-model="block.body" maxlength="300" class="block w-full border-0 bg-transparent p-0 text-sm text-muted placeholder:opacity-50 focus:outline-none focus:ring-0" placeholder="Short description (optional)">
                                                <input type="hidden" :name="`blocks[${block.key}][caption]`" :value="block.caption || ''">
                                                <p class="mt-0.5 text-xs text-faint">
                                                    <template x-if="block.up && block.up.status === 'uploading'"><span x-text="'Uploading ' + block.up.progress + '%…'"></span></template>
                                                    <template x-if="block.up && block.up.status === 'error'"><span class="text-rose-600" x-text="block.up.error"></span></template>
                                                    <template x-if="!block.up"><span x-text="block.existing ? (block.caption || '') + (block.size ? ' · ' + block.size : '') : 'PDF, Word, Excel, PowerPoint, CSV or ZIP · up to 25 MB'"></span></template>
                                                </p>
                                            </div>
                                            <label class="btn btn-light btn-sm shrink-0 cursor-pointer"><x-icon name="upload" class="size-4"/> <span x-text="block.existing ? 'Replace' : 'Choose file'"></span><input type="file" class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.txt" @change="pickFile(block, $event)"></label>
                                        </div>
                                    </template>
                                    {{-- Spacer --}}
                                    <template x-if="block.type === 'spacer'">
                                        <div class="flex items-center px-6" :class="{ s: 'h-6', m: 'h-12', l: 'h-20' }[block.settings.space]">
                                            <div class="w-full" :class="block.settings.line === '1' ? 'h-px bg-line' : 'h-full rounded-lg border border-dashed border-zinc-200'"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- insert between --}}
                            <div class="absolute -bottom-3.5 left-1/2 z-20 -translate-x-1/2" :class="picker === i + 1 ? '' : 'opacity-0 group-hover/b:opacity-100'">
                                <button type="button" @click.stop="picker = picker === i + 1 ? null : i + 1" class="grid size-7 place-items-center rounded-full bg-brand text-white shadow-md transition hover:scale-110" title="Insert block here"><x-icon name="plus" class="size-4"/></button>
                            </div>
                            <div x-show="picker === i + 1" x-cloak x-transition.origin.top @click.outside="picker = null" class="absolute left-1/2 top-full z-30 mt-5 w-[min(620px,90vw)] -translate-x-1/2 rounded-2xl border border-line bg-white p-2 shadow-xl">
                                <div class="grid grid-cols-3 gap-1 sm:grid-cols-5">
                                    @foreach($flat as $type => [$wl, $wi])
                                        <button type="button" @click.stop="insert('{{ $type }}', i + 1); {{ in_array($type, ['stats', 'faq'], true) ? "ensureItems(current, ".($type === 'stats' ? 3 : 2).")" : '' }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2.5 text-xs font-medium hover:bg-zinc-100"><x-icon :name="$wi" class="size-5 text-ink-soft"/>{{ $wl }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="drag && dropAt === blocks.length && blocks.length" class="mx-6 my-1 h-1 rounded-full bg-brand"></div>

                <div class="px-6 pt-6 sm:px-10">
                    <div x-show="!blocks.length" class="mx-auto max-w-2xl rounded-2xl border-2 border-dashed px-6 py-12 text-center transition" :class="drag ? 'border-brand bg-brand/5' : 'border-zinc-300'">
                        <p class="font-display text-lg font-bold">Start writing</p>
                        <p class="mt-1 text-sm text-muted">Drag blocks here from the panel, or add the first one:</p>
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <button type="button" @click.stop="insert('text', 0)" class="btn btn-light btn-sm"><x-icon name="paragraph" class="size-4"/> Text</button>
                            <button type="button" @click.stop="insert('heading', 0)" class="btn btn-light btn-sm"><x-icon name="heading" class="size-4"/> Heading</button>
                            <button type="button" @click.stop="insert('stats', 0); ensureItems(current, 3)" class="btn btn-light btn-sm"><x-icon name="chart" class="size-4"/> Key figures</button>
                            <button type="button" @click.stop="insert('image', 0)" class="btn btn-light btn-sm"><x-icon name="image" class="size-4"/> Image</button>
                        </div>
                    </div>
                    <div class="relative mt-4 flex justify-center">
                        <button type="button" @click.stop="picker = picker === 'end' ? null : 'end'" class="btn btn-light btn-pill"><x-icon name="plus" class="size-4"/> Add block</button>
                        <div x-show="picker === 'end'" x-cloak x-transition.origin.bottom @click.outside="picker = null" class="absolute bottom-full z-30 mb-2 w-[min(620px,90vw)] rounded-2xl border border-line bg-white p-2 shadow-xl">
                            <div class="grid grid-cols-3 gap-1 sm:grid-cols-5">
                                @foreach($flat as $type => [$wl, $wi])
                                    <button type="button" @click.stop="insert('{{ $type }}', blocks.length); {{ in_array($type, ['stats', 'faq'], true) ? "ensureItems(current, ".($type === 'stats' ? 3 : 2).")" : '' }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2.5 text-xs font-medium hover:bg-zinc-100"><x-icon :name="$wi" class="size-5 text-ink-soft"/>{{ $wl }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 sm:px-10"><x-field-error name="blocks"/></div>
        </div>
    </div>

    {{-- Undo toast --}}
    <div x-show="trash" x-cloak x-transition class="fixed bottom-6 left-1/2 z-50 flex -translate-x-1/2 items-center gap-4 rounded-full bg-ink py-2 pl-5 pr-2 text-sm text-white shadow-xl">
        <span><span x-text="trash && labels[trash.block.type]"></span> block deleted</span>
        <button type="button" @click="undo()" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 font-medium hover:bg-white/25"><x-icon name="undo" class="size-4"/> Undo</button>
    </div>
</form>

@if($post->exists)
    <form id="post-duplicate" method="POST" action="{{ route('admin.posts.duplicate', $post) }}" class="hidden">@csrf</form>
    <form id="post-feature" method="POST" action="{{ route('admin.posts.status', $post) }}" class="hidden">@csrf<input type="hidden" name="action" value="{{ $post->featured_at ? 'unfeature' : 'feature' }}"></form>
    <form id="post-archive" method="POST" action="{{ route('admin.posts.status', $post) }}" class="hidden">@csrf<input type="hidden" name="action" value="archive"></form>
    <form id="post-delete" method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="hidden" onsubmit="return confirm('Delete “{{ addslashes($post->title) }}” permanently? This cannot be undone.')">@csrf @method('DELETE')</form>
@endif
</x-layouts.admin>
