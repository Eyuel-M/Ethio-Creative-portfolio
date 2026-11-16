@php
    $blocks = old('blocks') ? collect(old('blocks'))->values() : $project->blocks->map(fn ($b) => ['type' => $b->type, 'body' => $b->body, 'caption' => $b->caption, 'alt' => $b->alt, 'embed_url' => $b->embed_url, 'existing' => $b->media_path, 'preview' => $b->mediaUrl()]);
@endphp
<x-layouts.app :title="$project->exists ? 'Edit project' : 'New project'">
<form method="POST" enctype="multipart/form-data" action="{{ $project->exists ? route('portfolio.update', $project) : route('portfolio.store') }}"
      x-data="portfolioBuilder(@js($blocks))" class="grid gap-3 sm:gap-4 xl:grid-cols-[1fr_340px]">
    @csrf @if($project->exists) @method('PUT') @endif
    <div class="space-y-3 sm:space-y-4">
        <section class="card card-pad">
            <a href="{{ route('portfolio.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> Portfolio</a>
            <h1 class="mt-3 font-display text-3xl font-bold">{{ $project->exists ? 'Edit case study' : 'New case study' }}</h1>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label" for="title">Project title</label><input id="title" name="title" value="{{ old('title', $project->title) }}" class="input" required maxlength="140"><x-field-error name="title"/></div>
                <div class="sm:col-span-2"><label class="label" for="summary">Short summary</label><textarea id="summary" name="summary" rows="2" class="input" maxlength="500">{{ old('summary', $project->summary) }}</textarea></div>
                <div><label class="label" for="category_id">Category</label><select id="category_id" name="category_id" class="input"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $project->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="label" for="project_date">Date</label><input id="project_date" type="date" name="project_date" value="{{ old('project_date', $project->project_date?->format('Y-m-d')) }}" class="input"></div>
                <div><label class="label" for="tags">Tags</label><input id="tags" name="tags" value="{{ old('tags', implode(', ', $project->tags ?? [])) }}" class="input" placeholder="Branding, Packaging"><p class="hint">Comma separated</p></div>
                <div><label class="label" for="client_attribution">Client (optional)</label><input id="client_attribution" name="client_attribution" value="{{ old('client_attribution', $project->client_attribution) }}" class="input" placeholder="Only with permission"></div>
            </div>
        </section>

        <section class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="panel-title">Content blocks</h2><p class="text-sm text-muted">Tell the story: problem, process, outcome.</p></div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-sm btn-light" @click="add('text')"><x-icon name="type" class="size-4"/> Text</button>
                    <button type="button" class="btn btn-sm btn-light" @click="add('image')"><x-icon name="image" class="size-4"/> Image</button>
                    <button type="button" class="btn btn-sm btn-light" @click="add('embed')"><x-icon name="video" class="size-4"/> Video</button>
                </div>
            </div>
            <div class="mt-5 space-y-3">
                <template x-for="(block, i) in blocks" :key="block.key">
                    <div class="rounded-2xl border border-line bg-zinc-50/60 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted" x-text="{text: 'Text', image: 'Image', embed: 'Video embed'}[block.type]"></span>
                            <div class="flex gap-1">
                                <button type="button" class="rounded-lg p-1.5 hover:bg-white" @click="move(i, -1)" :disabled="i === 0" aria-label="Move up"><x-icon name="chevron-down" class="size-4 rotate-180"/></button>
                                <button type="button" class="rounded-lg p-1.5 hover:bg-white" @click="move(i, 1)" :disabled="i === blocks.length - 1" aria-label="Move down"><x-icon name="chevron-down" class="size-4"/></button>
                                <button type="button" class="rounded-lg p-1.5 text-rose-600 hover:bg-white" @click="blocks.splice(i, 1)" aria-label="Remove block"><x-icon name="trash" class="size-4"/></button>
                            </div>
                        </div>
                        <input type="hidden" :name="`blocks[${block.key}][type]`" :value="block.type">
                        <template x-if="block.type === 'text'">
                            <textarea :name="`blocks[${block.key}][body]`" x-model="block.body" rows="4" class="input" placeholder="Describe the brief, your process and the result…"></textarea>
                        </template>
                        <template x-if="block.type === 'image'">
                            <div class="grid gap-3 sm:grid-cols-[160px_1fr]">
                                <label class="grid aspect-[4/3] cursor-pointer place-items-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-white text-faint">
                                    <img x-show="block.preview" :src="block.preview" class="size-full object-cover" alt="">
                                    <span x-show="!block.preview" class="text-center text-xs"><x-icon name="upload" class="mx-auto size-5"/>Upload</span>
                                    <input type="file" accept="image/*" class="sr-only" :name="`blocks[${block.key}][image]`" @change="block.preview = URL.createObjectURL($event.target.files[0])">
                                </label>
                                <div class="space-y-2">
                                    <input type="hidden" :name="`blocks[${block.key}][existing]`" :value="block.existing">
                                    <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" class="input" placeholder="Caption">
                                    <input :name="`blocks[${block.key}][alt]`" x-model="block.alt" class="input" placeholder="Alt text (describes the image for screen readers)">
                                </div>
                            </div>
                        </template>
                        <template x-if="block.type === 'embed'">
                            <div class="space-y-2">
                                <input :name="`blocks[${block.key}][embed_url]`" x-model="block.embed_url" type="url" class="input" placeholder="https://www.youtube.com/watch?v=… or https://vimeo.com/…">
                                <input :name="`blocks[${block.key}][caption]`" x-model="block.caption" class="input" placeholder="Caption">
                            </div>
                        </template>
                    </div>
                </template>
                <p x-show="!blocks.length" class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-muted">No blocks yet — add text, images or a video above.</p>
            </div>
            <x-field-error name="blocks"/>
        </section>
    </div>

    <aside class="space-y-3 sm:space-y-4 xl:sticky xl:top-4 xl:h-fit">
        <section class="card card-pad" x-data="{ preview: @js($project->coverUrl()) }">
            <h2 class="font-semibold">Cover image</h2>
            <label class="mt-3 grid aspect-[4/3] cursor-pointer place-items-center overflow-hidden rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 text-faint">
                <img x-show="preview" :src="preview" class="size-full object-cover" alt="">
                <span x-show="!preview" class="text-center text-sm"><x-icon name="upload" class="mx-auto size-6"/>Upload cover</span>
                <input type="file" name="cover" accept="image/*" class="sr-only" @change="preview = URL.createObjectURL($event.target.files[0])">
            </label>
            <x-field-error name="cover"/>
            <input name="cover_alt" value="{{ old('cover_alt', $project->cover_alt) }}" class="input mt-3" placeholder="Cover alt text">
            <p class="hint">JPG, PNG, WebP or GIF up to 20 MB.</p>
        </section>
        <section class="card card-pad space-y-3">
            <div class="flex items-center justify-between"><span class="text-sm text-muted">Status</span><x-status :status="$project->status ?? 'draft'"/></div>
            <button name="publish" value="0" class="btn btn-light w-full">Save draft</button>
            <button name="publish" value="1" class="btn btn-dark w-full">{{ $project->isPublished() ? 'Save & keep published' : 'Save & publish' }}</button>
            @if($project->exists)<a href="{{ route('portfolio.show', $project) }}" class="btn btn-ghost w-full"><x-icon name="eye" class="size-4"/> Preview</a>@endif
        </section>
    </aside>
</form>
@if($project->exists)
    <form method="POST" action="{{ route('portfolio.destroy', $project) }}" onsubmit="return confirm('Delete this project permanently?')" class="text-right">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><x-icon name="trash" class="size-4"/> Delete project</button></form>
@endif
<script>
function portfolioBuilder(initial) {
    let n = 0;
    return {
        blocks: initial.map(b => ({ ...b, key: n++ })),
        add(type) { this.blocks.push({ key: n++ + 1000, type, body: '', caption: '', alt: '', embed_url: '', existing: null, preview: null }); },
        move(i, d) { const j = i + d; if (j < 0 || j >= this.blocks.length) return; const b = this.blocks.splice(i, 1)[0]; this.blocks.splice(j, 0, b); },
    };
}
</script>
</x-layouts.app>
