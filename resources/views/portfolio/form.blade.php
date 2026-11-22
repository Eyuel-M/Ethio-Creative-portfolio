@php
    use App\Models\PortfolioBlock;
    use App\Support\Media;
    $url = fn ($p) => $p ? Media::url($p) : null;
    $blocks = old('blocks')
        ? collect(old('blocks'))->values()->map(fn ($b) => [
            'type' => $b['type'] ?? 'text', 'body' => $b['body'] ?? '', 'caption' => $b['caption'] ?? '', 'alt' => $b['alt'] ?? '', 'embed_url' => $b['embed_url'] ?? '',
            'existing' => $b['existing'] ?? null, 'preview' => $url($b['existing'] ?? null),
            'gallery' => collect($b['gallery'] ?? [])->map(fn ($g) => ['path' => $g['path'], 'alt' => $g['alt'] ?? '', 'url' => $url($g['path'])])->values(),
            'settings' => json_decode($b['settings'] ?? '', true) ?: [],
        ])
        : $project->blocks->map(fn ($b) => [
            'type' => $b->type, 'body' => $b->body ?? '', 'caption' => $b->caption ?? '', 'alt' => $b->alt ?? '', 'embed_url' => $b->embed_url ?? '',
            'existing' => $b->media_path, 'preview' => $b->mediaUrl(),
            'gallery' => collect($b->media ?? [])->map(fn ($g) => ['path' => $g['path'], 'alt' => $g['alt'] ?? '', 'url' => $url($g['path'])])->values(),
            'settings' => $b->settings ?? [],
        ]);
    $widgets = [
        'heading' => ['Heading', 'heading', 'Section title'],
        'text' => ['Text', 'paragraph', 'Paragraphs'],
        'image' => ['Image', 'image', 'One picture'],
        'gallery' => ['Gallery', 'gallery', 'Grid of images'],
        'image_text' => ['Image + text', 'columns', 'Side by side'],
        'quote' => ['Quote', 'quote', 'Testimonial'],
        'embed' => ['Video', 'video', 'YouTube / Vimeo'],
        'spacer' => ['Spacer', 'spacer', 'Space or line'],
    ];
    $labels = collect($widgets)->map(fn ($w) => $w[0]);
    $controls = [
        'Layout' => [
            'level' => ['Heading size', ['h2' => 'Large', 'h3' => 'Medium']],
            'size' => ['Text size', ['base' => 'Normal', 'lg' => 'Large']],
            'cols' => ['Columns', ['2' => '2', '3' => '3', '4' => '4']],
            'gap' => ['Gap between images', ['none' => 'None', 's' => 'Small', 'm' => 'Large']],
            'side' => ['Image position', ['left' => 'Left', 'right' => 'Right']],
            'align' => ['Alignment', ['left' => 'Left', 'center' => 'Center']],
            'width' => ['Width', ['narrow' => 'Narrow', 'wide' => 'Wide', 'full' => 'Edge to edge']],
            'space' => ['Height', ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large']],
            'line' => ['Divider line', ['0' => 'Off', '1' => 'On']],
        ],
        'Style' => [
            'bg' => ['Background', ['none' => 'None', 'light' => 'Light', 'dark' => 'Dark', 'accent' => 'Tint']],
            'pad' => ['Vertical padding', ['none' => 'None', 's' => 'S', 'm' => 'M', 'l' => 'L']],
        ],
    ];
    $swatch = ['none' => 'bg-white', 'light' => 'bg-zinc-100', 'dark' => 'bg-ink', 'accent' => 'bg-violet-100'];
    $seg = 'flex-1 rounded-lg px-2 py-1.5 text-xs font-medium transition';
    // Upload state shown over an image: progress while uploading, size saved when done, retry on failure.
    $progress = fn (string $t, string $d) => <<<HTML
        <template x-if="{$t}.up">
            <div class="absolute inset-0 z-[6] grid place-items-center transition" :class="{$t}.up.status === 'error' ? 'bg-rose-950/60' : ({$t}.up.status === 'done' ? 'bg-transparent' : 'bg-black/40')" @click.prevent.stop>
                <div x-show="{$t}.up.status === 'uploading'" class="w-2/3 max-w-[220px] text-center text-white">
                    <p class="text-xs font-semibold drop-shadow" x-text="{$t}.up.stage === 'Uploading' ? 'Uploading ' + {$t}.up.progress + '%' : {$t}.up.stage"></p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/30"><div class="h-full rounded-full bg-white transition-all" :style="'width:' + {$t}.up.progress + '%'"></div></div>
                </div>
                <div x-show="{$t}.up.status === 'done'" class="absolute bottom-2 left-2 inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white shadow"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 5 5L20 7"/></svg><span x-text="{$t}.up.note"></span></div>
                <div x-show="{$t}.up.status === 'error'" class="px-3 text-center text-white">
                    <p class="text-xs font-semibold" x-text="{$t}.up.error"></p>
                    <button type="button" @click.stop="retry({$t}, {$d})" class="mt-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-ink hover:bg-zinc-100">Retry</button>
                </div>
            </div>
        </template>
    HTML;
@endphp
<x-layouts.app :title="$project->exists ? 'Edit project' : 'New project'">
<form method="POST" enctype="multipart/form-data" action="{{ $project->exists ? route('portfolio.update', $project) : route('portfolio.store') }}"
      x-data="portfolioBuilder({ blocks: @js($blocks), defaults: @js(PortfolioBlock::TYPE_SETTINGS), labels: @js($labels), uploadUrl: @js(route('media.uploads.store')), cover: @js(old('cover_upload') ? $url(old('cover_upload')) : $project->coverUrl()), coverPath: @js(old('cover_upload')) })" @submit="beforeSubmit($event)"
      @keydown.escape.window="deselect(); picker = null" @dragend.window="endDrag()" class="space-y-3 sm:space-y-4">
    @csrf @if($project->exists) @method('PUT') @endif

    {{-- ───────── Top bar ───────── --}}
    <div class="card sticky top-[4.5rem] z-30 flex flex-wrap items-center gap-3 px-4 py-3 shadow-[0_6px_20px_-12px_rgba(12,12,16,.25)] sm:px-5">
        <a href="{{ route('portfolio.index') }}" class="grid size-9 place-items-center rounded-lg hover:bg-zinc-100" aria-label="Back to portfolio"><x-icon name="chevron-left" class="size-5"/></a>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold">{{ $project->exists ? 'Edit case study' : 'New case study' }}</p>
            <p class="flex items-center gap-2 text-xs text-muted"><x-status :status="$project->status ?? 'draft'"/> <span x-text="blocks.length + (blocks.length === 1 ? ' block' : ' blocks')"></span></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($project->exists)<a href="{{ route('portfolio.show', $project) }}" target="_blank" class="btn btn-light h-10"><x-icon name="eye" class="size-4"/> <span class="hidden sm:inline">Preview</span></a>@endif
            <span x-show="uploading" x-cloak class="inline-flex items-center gap-2 text-xs font-medium text-muted"><span class="size-3.5 animate-spin rounded-full border-2 border-zinc-300 border-t-ink"></span><span x-text="'Uploading ' + uploading + (uploading === 1 ? ' image' : ' images') + '…'"></span></span>
            <button name="publish" value="0" class="btn btn-light h-10" :disabled="uploading > 0" :class="uploading > 0 && 'opacity-50'">Save draft</button>
            <button name="publish" value="1" class="btn btn-dark h-10" :disabled="uploading > 0" :class="uploading > 0 && 'opacity-50'">{{ $project->isPublished() ? 'Save & update' : 'Publish' }}</button>
        </div>
    </div>

    <div x-show="notice" x-cloak class="flex items-center justify-between gap-3 rounded-2xl bg-amber-50 px-5 py-3 text-sm text-amber-800"><span x-text="notice"></span><button type="button" @click="notice = ''" class="text-amber-700 hover:text-amber-900" aria-label="Dismiss"><x-icon name="x" class="size-4"/></button></div>
    @if($errors->any())
        <div class="rounded-2xl bg-rose-50 px-5 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 items-start gap-3 sm:gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">

        {{-- ───────── Panel ───────── --}}
        <aside data-fit="scroll" class="card overflow-hidden lg:sticky lg:top-[9.5rem] lg:flex lg:max-h-[calc(100vh-11rem)] lg:flex-col">
            <div class="flex border-b border-line p-1.5">
                @foreach(['add' => ['Add', 'plus'], 'style' => ['Style', 'settings'], 'project' => ['Project', 'file']] as $t => [$tl, $ti])
                    <button type="button" @click="tab = '{{ $t }}'" class="flex flex-1 items-center justify-center gap-1.5 rounded-lg py-2 text-sm font-medium transition" :class="tab === '{{ $t }}' ? 'bg-ink text-white' : 'text-ink-soft hover:bg-zinc-100'"><x-icon :name="$ti" class="size-4"/> {{ $tl }}</button>
                @endforeach
            </div>

            <div class="overflow-y-auto p-4">
                {{-- Add --}}
                <div x-show="tab === 'add'">
                    <p class="text-xs text-muted">Drag a block onto the page, or click to add it <span x-text="current ? 'after the selected block' : 'at the end'"></span>.</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @foreach($widgets as $type => [$wl, $wi, $wd])
                            <button type="button" draggable="true" @dragstart="startDrag($event, { type: '{{ $type }}' })" @click="insert('{{ $type }}')"
                                    class="group flex cursor-grab flex-col items-center gap-1.5 rounded-xl border border-line bg-white px-2 py-4 text-center transition hover:-translate-y-0.5 hover:border-ink hover:shadow-sm active:cursor-grabbing">
                                <span class="grid size-9 place-items-center rounded-lg bg-zinc-100 text-ink-soft transition group-hover:bg-ink group-hover:text-white"><x-icon :name="$wi" class="size-[18px]"/></span>
                                <span class="text-[13px] font-semibold">{{ $wl }}</span>
                                <span class="text-[11px] leading-tight text-muted">{{ $wd }}</span>
                            </button>
                        @endforeach
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
                                    <textarea data-alt-field x-model="current.alt" rows="3" maxlength="200" class="input h-auto flex-1 py-2" aria-label="Alt text" placeholder="e.g. Coffee bags in three colours on a wooden shelf"></textarea>
                                </div>
                                <p class="text-[11px] leading-snug text-muted">Describes the image for people using screen readers and when it fails to load.</p>
                            </div>
                            <div x-show="current.type === 'gallery'" class="space-y-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">Alt text <span class="font-normal normal-case tracking-normal" x-text="'· ' + (current.gallery.length - missingAlt(current)) + ' of ' + current.gallery.length + ' done'"></span></p>
                                <template x-for="(g, gi) in current.gallery" :key="g.id">
                                    <div class="flex gap-3">
                                        <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><img :src="g.url" alt="" class="size-full object-cover"></span>
                                        <textarea :data-alt-field="gi === 0 ? '' : null" x-model="g.alt" rows="2" maxlength="200" class="input h-auto flex-1 py-1.5 text-[13px]" :aria-label="`Alt text for image ${gi + 1}`" :placeholder="`Describe image ${gi + 1}`"></textarea>
                                    </div>
                                </template>
                                <p x-show="!current.gallery.length" class="text-xs text-muted">Add images to the gallery first.</p>
                                <p class="text-[11px] leading-snug text-muted">Describes each image for people using screen readers.</p>
                            </div>
                            @foreach($controls as $group => $items)
                                <div class="space-y-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-faint">{{ $group }}</p>
                                    @foreach($items as $key => [$cl, $opts])
                                        <div x-show="has('{{ $key }}')">
                                            <p class="mb-1.5 text-xs font-medium text-ink-soft">{{ $cl }}</p>
                                            @if($key === 'bg')
                                                <div class="flex gap-2">
                                                    @foreach($opts as $v => $ol)
                                                        <button type="button" @click="set('bg', '{{ $v }}')" class="flex flex-1 flex-col items-center gap-1 rounded-xl border p-2 text-[11px] font-medium transition" :class="current.settings.bg === '{{ $v }}' ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-zinc-300'">
                                                            <span class="size-6 rounded-full border border-line {{ $swatch[$v] }}"></span>{{ $ol }}
                                                        </button>
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

                {{-- Project --}}
                <div x-show="tab === 'project'" x-cloak class="space-y-4">
                    <div><label class="label" for="category_id">Category</label><select id="category_id" name="category_id" class="input"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $project->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                    <div><label class="label" for="project_date">Date</label><input id="project_date" type="date" name="project_date" value="{{ old('project_date', $project->project_date?->format('Y-m-d')) }}" class="input"></div>
                    <div><label class="label" for="tags">Tags</label><input id="tags" name="tags" value="{{ old('tags', implode(', ', $project->tags ?? [])) }}" class="input" placeholder="Branding, Packaging"><p class="hint">Comma separated</p></div>
                    <div><label class="label" for="client_attribution">Client <span class="font-normal text-muted">(optional)</span></label><input id="client_attribution" name="client_attribution" value="{{ old('client_attribution', $project->client_attribution) }}" class="input" placeholder="Only with permission"></div>
                </div>
            </div>
        </aside>

        {{-- ───────── Canvas ───────── --}}
        <div class="card overflow-hidden" @click.self="deselect()">
            {{-- Page header --}}
            <div class="px-6 pb-8 pt-10 sm:px-10" @click="deselect()">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand">Case study</p>
                <label class="sr-only" for="title">Project title</label>
                <textarea id="title" name="title" rows="1" required maxlength="140" x-init="grow($el)" @input="grow($el)" class="mt-3 block w-full resize-none border-0 bg-transparent p-0 font-display text-4xl font-bold leading-[1.05] tracking-tight placeholder:text-zinc-300 focus:outline-none focus:ring-0 sm:text-5xl" placeholder="Project title">{{ old('title', $project->title) }}</textarea>
                <x-field-error name="title"/>
                <label class="sr-only" for="summary">Short summary</label>
                <textarea id="summary" name="summary" rows="1" maxlength="500" x-init="grow($el)" @input="grow($el)" class="mt-4 block w-full max-w-3xl resize-none border-0 bg-transparent p-0 text-lg leading-relaxed text-ink-soft placeholder:text-zinc-300 focus:outline-none focus:ring-0 sm:text-xl" placeholder="One or two sentences about the project…">{{ old('summary', $project->summary) }}</textarea>
            </div>

            {{-- Cover --}}
            <div @click="deselect()">
                <input type="hidden" name="cover_upload" :value="cover.path || ''">
                <label class="group relative block cursor-pointer">
                    <template x-if="cover.preview"><div class="px-2"><img :src="cover.preview" alt="" class="block h-auto max-h-[520px] w-full rounded-xl object-cover"></div></template>
                    <div x-show="!cover.preview" class="mx-6 grid aspect-[21/9] place-items-center rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 text-center text-muted transition group-hover:border-ink sm:mx-10">
                        <span><x-icon name="upload" class="mx-auto size-7"/><span class="mt-2 block text-sm font-medium text-ink">Add a cover image</span><span class="text-xs">Shown first on your project and in feeds · compressed automatically</span></span>
                    </div>
                    <span x-show="cover.preview && !cover.up" class="absolute right-4 top-4 z-[7] inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium opacity-0 shadow backdrop-blur transition group-hover:opacity-100"><x-icon name="image" class="size-4"/> Replace cover</span>
                    {!! $progress('cover', 'coverDone()') !!}
                    <input type="file" accept="image/*" class="sr-only" @change="pickCover($event)">
                </label>
                <div x-show="cover.preview" class="flex items-center gap-2 px-6 pt-3 sm:px-10">
                    <label for="cover_alt" class="shrink-0 rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wider text-muted">Alt</label>
                    <input id="cover_alt" name="cover_alt" value="{{ old('cover_alt', $project->cover_alt) }}" maxlength="200" class="block w-full border-0 bg-transparent py-1 text-sm text-ink-soft placeholder:text-zinc-400 focus:outline-none focus:ring-0" placeholder="Describe the cover image for screen readers">
                </div>
                <div class="px-6 sm:px-10"><x-field-error name="cover"/></div>
            </div>

            {{-- Blocks --}}
            <div class="relative mt-2 min-h-[160px] pb-6" @dragover.prevent="if (drag && !blocks.length) dropAt = 0" @drop.prevent="drop()">
                <template x-for="(block, i) in blocks" :key="block.key">
                    <div>
                        <div x-show="drag && dropAt === i" class="mx-6 my-1 h-1 rounded-full bg-brand"></div>
                        <div :id="`block-${block.key}`" class="group/b relative" @click.stop="select(block)" @dragover.prevent.stop="over($event, i)" @drop.prevent.stop="drop()" :class="drag && drag.from === i && 'opacity-40'">
                            {{-- outline --}}
                            <div class="pointer-events-none absolute inset-0 z-10 transition" :class="selected === block.key ? 'ring-2 ring-inset ring-brand' : 'group-hover/b:ring-1 group-hover/b:ring-inset group-hover/b:ring-brand/40'"></div>
                            {{-- label --}}
                            <span class="absolute left-3 top-3 z-20 rounded-md bg-brand px-2 py-0.5 text-[11px] font-semibold text-white" x-show="selected === block.key" x-text="labels[block.type]"></span>
                            {{-- missing alt text reminder --}}
                            <button type="button" x-show="missingAlt(block)" x-cloak @click.stop="editAlt(block)" class="absolute bottom-3 left-3 z-20 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800 shadow-sm hover:bg-amber-200">
                                <x-icon name="pen" class="size-3"/> <span x-text="block.type === 'gallery' ? missingAlt(block) + ' missing alt text' : 'Add alt text'"></span>
                            </button>
                            {{-- toolbar --}}
                            <div class="absolute right-3 top-3 z-20 flex items-center gap-0.5 rounded-lg border border-line bg-white p-0.5 shadow-md transition" :class="selected === block.key ? 'opacity-100' : 'pointer-events-none opacity-0 group-hover/b:pointer-events-auto group-hover/b:opacity-100'">
                                <span draggable="true" @dragstart.stop="startDrag($event, { from: i })" class="grid size-7 cursor-grab place-items-center rounded-md text-muted hover:bg-zinc-100 active:cursor-grabbing" title="Drag to reorder"><x-icon name="grip" class="size-4"/></span>
                                <button type="button" @click.stop="move(i, -1)" :disabled="i === 0" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100 disabled:opacity-30" title="Move up"><x-icon name="chevron-down" class="size-4 rotate-180"/></button>
                                <button type="button" @click.stop="move(i, 1)" :disabled="i === blocks.length - 1" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100 disabled:opacity-30" title="Move down"><x-icon name="chevron-down" class="size-4"/></button>
                                <button type="button" @click.stop="duplicate(i)" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100" title="Duplicate"><x-icon name="copy" class="size-4"/></button>
                                <button type="button" @click.stop="select(block); tab = 'style'" class="grid size-7 place-items-center rounded-md hover:bg-zinc-100" title="Style"><x-icon name="settings" class="size-4"/></button>
                                <button type="button" @click.stop="remove(i)" class="grid size-7 place-items-center rounded-md text-rose-600 hover:bg-rose-50" title="Delete"><x-icon name="trash" class="size-4"/></button>
                            </div>

                            {{-- data that isn't edited directly on the canvas --}}
                            <input type="hidden" :name="`blocks[${block.key}][type]`" :value="block.type">
                            <input type="hidden" :name="`blocks[${block.key}][settings]`" :value="JSON.stringify(block.settings)">
                            <template x-if="['image', 'image_text'].includes(block.type)"><span>
                                <input type="hidden" :name="`blocks[${block.key}][existing]`" :value="block.existing || ''">
                                <input type="hidden" :name="`blocks[${block.key}][alt]`" :value="block.alt || ''">
                            </span></template>
                            <template x-for="(g, gi) in block.gallery.filter((x) => x.path)" :key="'h' + g.id">
                                <span><input type="hidden" :name="`blocks[${block.key}][gallery][${gi}][path]`" :value="g.path"><input type="hidden" :name="`blocks[${block.key}][gallery][${gi}][alt]`" :value="g.alt || ''"></span>
                            </template>

                            {{-- content --}}
                            <div :class="wrap(block)">
                                <div :class="inner(block)">
                                    <template x-if="block.type === 'heading'">
                                        <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="1" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="300"
                                                  class="block w-full resize-none border-0 bg-transparent p-0 font-display font-bold leading-tight tracking-tight placeholder:opacity-30 focus:outline-none focus:ring-0"
                                                  :class="[block.settings.level === 'h3' ? 'text-2xl sm:text-3xl' : 'text-3xl sm:text-4xl', block.settings.align === 'center' && 'text-center']" placeholder="Section heading"></textarea>
                                    </template>
                                    <template x-if="block.type === 'text'">
                                        <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="2" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="5000"
                                                  class="block w-full resize-none border-0 bg-transparent p-0 leading-relaxed placeholder:opacity-40 focus:outline-none focus:ring-0"
                                                  :class="[block.settings.size === 'lg' ? 'text-xl' : 'text-[17px]', block.settings.bg === 'dark' ? 'text-white/80' : 'text-ink-soft', block.settings.align === 'center' && 'text-center']" placeholder="Write about the brief, your process or the outcome…"></textarea>
                                    </template>
                                    <template x-if="block.type === 'image'">
                                        <figure>
                                            <label class="relative block cursor-pointer">
                                                <template x-if="block.preview"><img :src="block.preview" alt="" class="block h-auto w-full" :class="block.settings.width !== 'full' ? 'rounded-2xl' : 'rounded-xl'"></template>
                                                <div x-show="!block.preview" class="grid aspect-[16/9] place-items-center border-2 border-dashed border-zinc-300 bg-zinc-50 text-center text-muted hover:border-ink" :class="block.settings.width === 'full' ? 'mx-6 rounded-2xl sm:mx-10' : 'rounded-2xl'"><span><x-icon name="upload" class="mx-auto size-6"/><span class="mt-1 block text-sm font-medium text-ink">Upload an image</span></span></div>
                                                {!! $progress('block', 'imageDone(block)') !!}
                                                <input type="file" accept="image/*" class="sr-only" @change="pickImage(block, $event)">
                                            </label>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="200" class="mt-2 block w-full border-0 bg-transparent py-1 text-sm text-muted placeholder:text-zinc-300 focus:outline-none focus:ring-0" :class="block.settings.width === 'full' && 'px-6 sm:px-10'" placeholder="Add a caption (optional)">
                                        </figure>
                                    </template>
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
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="200" class="mt-2 block w-full border-0 bg-transparent px-4 py-1 text-sm text-muted placeholder:text-zinc-300 focus:outline-none focus:ring-0" placeholder="Add a caption (optional)">
                                        </figure>
                                    </template>
                                    <template x-if="block.type === 'image_text'">
                                        <div class="grid items-center gap-6 sm:grid-cols-2">
                                            <label class="relative block cursor-pointer" :class="block.settings.side === 'right' && 'sm:order-2'">
                                                <template x-if="block.preview"><img :src="block.preview" alt="" class="block h-auto w-full rounded-2xl"></template>
                                                <div x-show="!block.preview" class="grid aspect-[4/3] place-items-center rounded-2xl border-2 border-dashed border-zinc-300 bg-white/60 text-center text-muted hover:border-ink"><span><x-icon name="upload" class="mx-auto size-6"/><span class="mt-1 block text-sm font-medium text-ink">Upload an image</span></span></div>
                                                {!! $progress('block', 'imageDone(block)') !!}
                                                <input type="file" accept="image/*" class="sr-only" @change="pickImage(block, $event)">
                                            </label>
                                            <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="5" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="5000"
                                                      class="block w-full resize-none border-0 bg-transparent p-0 text-[17px] leading-relaxed placeholder:opacity-40 focus:outline-none focus:ring-0" :class="block.settings.bg === 'dark' ? 'text-white/80' : 'text-ink-soft'" placeholder="Title on the first line&#10;Then describe what the image shows…"></textarea>
                                        </div>
                                    </template>
                                    <template x-if="block.type === 'quote'">
                                        <figure>
                                            <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="2" x-init="$nextTick(() => grow($el))" @input="grow($el)" maxlength="1000"
                                                      class="block w-full resize-none border-0 bg-transparent p-0 font-display text-2xl font-semibold leading-snug tracking-tight placeholder:opacity-30 focus:outline-none focus:ring-0 sm:text-3xl" :class="block.settings.align === 'center' && 'text-center'" placeholder="“What the client said about working with you…”"></textarea>
                                            <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="200" class="mt-4 block w-full border-0 bg-transparent p-0 text-sm font-medium placeholder:opacity-40 focus:outline-none focus:ring-0" :class="[block.settings.align === 'center' && 'text-center', block.settings.bg === 'dark' ? 'text-white/60' : 'text-muted']" placeholder="— Name, role, company">
                                        </figure>
                                    </template>
                                    <template x-if="block.type === 'embed'">
                                        <figure :class="block.settings.width === 'full' || 'space-y-0'">
                                            <div class="aspect-video w-full overflow-hidden bg-zinc-900" :class="block.settings.width !== 'full' ? 'rounded-2xl' : 'rounded-xl'">
                                                <template x-if="embedSrc(block.embed_url)"><iframe :src="embedSrc(block.embed_url)" class="pointer-events-none size-full" title="Video preview"></iframe></template>
                                                <div x-show="!embedSrc(block.embed_url)" class="grid size-full place-items-center text-center text-white/60"><span><x-icon name="video" class="mx-auto size-8"/><span class="mt-2 block text-sm">Paste a YouTube or Vimeo link below</span></span></div>
                                            </div>
                                            <div class="mt-3 flex gap-2" :class="block.settings.width === 'full' && 'px-6 sm:px-10'">
                                                <input :name="`blocks[${block.key}][embed_url]`" x-model="block.embed_url" type="url" class="input h-10 flex-1" placeholder="https://www.youtube.com/watch?v=…">
                                                <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" maxlength="200" class="input h-10 flex-1" placeholder="Caption (optional)">
                                            </div>
                                        </figure>
                                    </template>
                                    <template x-if="block.type === 'spacer'">
                                        <div class="flex items-center px-6" :class="{ s: 'h-6', m: 'h-14', l: 'h-28' }[block.settings.space]">
                                            <div class="w-full" :class="block.settings.line === '1' ? 'h-px bg-line' : 'h-full rounded-lg border border-dashed border-zinc-200'"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- insert between --}}
                            <div class="absolute -bottom-3.5 left-1/2 z-20 -translate-x-1/2" :class="picker === i + 1 ? '' : 'opacity-0 group-hover/b:opacity-100'">
                                <button type="button" @click.stop="picker = picker === i + 1 ? null : i + 1" class="grid size-7 place-items-center rounded-full bg-brand text-white shadow-md transition hover:scale-110" title="Insert block here"><x-icon name="plus" class="size-4"/></button>
                            </div>
                            <div x-show="picker === i + 1" x-cloak x-transition.origin.top @click.outside="picker = null" class="absolute left-1/2 top-full z-30 mt-5 w-[min(560px,90vw)] -translate-x-1/2 rounded-2xl border border-line bg-white p-2 shadow-xl">
                                <div class="grid grid-cols-4 gap-1">
                                    @foreach($widgets as $type => [$wl, $wi])
                                        <button type="button" @click.stop="insert('{{ $type }}', i + 1)" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2.5 text-xs font-medium hover:bg-zinc-100"><x-icon :name="$wi" class="size-5 text-ink-soft"/>{{ $wl }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="drag && dropAt === blocks.length && blocks.length" class="mx-6 my-1 h-1 rounded-full bg-brand"></div>

                {{-- empty / end --}}
                <div class="px-6 pt-6 sm:px-10">
                    <div x-show="!blocks.length" class="rounded-2xl border-2 border-dashed px-6 py-12 text-center transition" :class="drag ? 'border-brand bg-brand/5' : 'border-zinc-300'">
                        <p class="font-display text-lg font-bold">Build your case study</p>
                        <p class="mt-1 text-sm text-muted">Drag blocks here from the panel, or start with one of these:</p>
                    </div>
                    <div class="relative mt-4 flex justify-center">
                        <button type="button" @click.stop="picker = picker === 'end' ? null : 'end'" class="btn btn-light btn-pill"><x-icon name="plus" class="size-4"/> Add block</button>
                        <div x-show="picker === 'end'" x-cloak x-transition.origin.bottom @click.outside="picker = null" class="absolute bottom-full z-30 mb-2 w-[min(560px,90vw)] rounded-2xl border border-line bg-white p-2 shadow-xl">
                            <div class="grid grid-cols-4 gap-1">
                                @foreach($widgets as $type => [$wl, $wi])
                                    <button type="button" @click.stop="insert('{{ $type }}', blocks.length)" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2.5 text-xs font-medium hover:bg-zinc-100"><x-icon :name="$wi" class="size-5 text-ink-soft"/>{{ $wl }}</button>
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

@if($project->exists)
    <form method="POST" action="{{ route('portfolio.destroy', $project) }}" onsubmit="return confirm('Delete this project permanently?')" class="text-right">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost text-rose-600"><x-icon name="trash" class="size-4"/> Delete project</button></form>
@endif
</x-layouts.app>
