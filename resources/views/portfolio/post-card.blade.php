<x-layouts.app title="Post card">
<div x-data="shareCard(@js($config))" class="space-y-3 sm:space-y-4">
    <section class="card card-pad flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('portfolio.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> My portfolio</a>
            <h1 class="mt-3 font-display text-3xl font-bold tracking-tight">Announce your portfolio</h1>
            <p class="mt-1 text-muted">A ready-to-post image made from your own work — for LinkedIn and Instagram.</p>
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-3 sm:gap-4 xl:grid-cols-[minmax(0,1fr)_400px]">
        {{-- Preview --}}
        <section class="card overflow-hidden xl:sticky xl:top-20">
            <div class="flex items-center justify-between border-b border-line px-5 py-3 text-sm">
                <span class="font-medium" x-text="formats[format].label + ' · ' + formats[format].w + '×' + formats[format].h"></span>
                <span class="text-xs text-muted">Preview</span>
            </div>
            <div class="grid place-items-center bg-[repeating-conic-gradient(#f4f4f5_0%_25%,#fafafa_0%_50%)] bg-[length:22px_22px] p-4 sm:p-8">
                <canvas x-ref="canvas" class="h-auto max-h-[70vh] w-auto max-w-full rounded-xl shadow-[0_20px_50px_-20px_rgba(12,12,16,.45)]" role="img" aria-label="Post card preview"></canvas>
            </div>
        </section>

        {{-- Controls --}}
        <aside class="space-y-3 sm:space-y-4">
            <section class="card card-pad space-y-5">
                <div>
                    <p class="label">Where will you post it?</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['linkedin' => ['LinkedIn post', 'Landscape'], 'linkedin_square' => ['LinkedIn square', '1:1'], 'instagram' => ['Instagram post', 'Portrait 4:5'], 'story' => ['Instagram story', '9:16']] as $k => [$l, $sub])
                            <button type="button" @click="format = '{{ $k }}'" class="rounded-xl border px-3 py-2.5 text-left transition" :class="format === '{{ $k }}' ? 'border-ink bg-zinc-50 ring-1 ring-ink' : 'border-line hover:border-zinc-300'">
                                <span class="block text-sm font-semibold">{{ $l }}</span><span class="block text-xs text-muted">{{ $sub }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="label">Style</p>
                    <div class="flex gap-2">
                        @foreach(['light' => ['Light', 'bg-[#fbfbfd]'], 'dark' => ['Dark', 'bg-ink'], 'brand' => ['Blue', 'bg-brand']] as $k => [$l, $sw])
                            <button type="button" @click="theme = '{{ $k }}'" class="flex flex-1 items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium transition" :class="theme === '{{ $k }}' ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-zinc-300'"><span class="size-5 rounded-full border border-line {{ $sw }}"></span>{{ $l }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="label">Headline</p>
                    <div class="space-y-1.5">
                        <template x-for="(h, i) in headlines" :key="i">
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border px-3 py-2 text-sm transition" :class="headline === i && ! custom.trim() ? 'border-ink bg-zinc-50' : 'border-line hover:border-zinc-300'">
                                <input type="radio" name="headline" :value="i" x-model.number="headline" @change="custom = ''" class="mt-0.5 accent-ink"><span x-text="h"></span>
                            </label>
                        </template>
                        <input x-model="custom" maxlength="90" class="input mt-1" placeholder="…or write your own">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="showLink" class="checkbox"> Show my portfolio link on the image</label>
            </section>

            <section class="card card-pad">
                <div class="flex items-baseline justify-between"><p class="label mb-0">Projects in the collage</p><span class="text-xs text-muted" x-text="picked.length + ' / ' + max"></span></div>
                <p class="mt-1 text-xs text-muted">Tap to add or remove. The order you pick is the order on the card.</p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <template x-for="p in projects" :key="p.id">
                        <button type="button" @click="toggle(p.id)" class="group relative aspect-[4/3] overflow-hidden rounded-lg bg-zinc-100 ring-2 ring-offset-2 transition" :class="order(p.id) ? 'ring-ink' : 'ring-transparent opacity-70 hover:opacity-100'" :title="p.title">
                            <img :src="p.cover" alt="" class="size-full object-cover">
                            <span x-show="order(p.id)" class="absolute right-1.5 top-1.5 grid size-6 place-items-center rounded-full bg-ink text-[11px] font-bold text-white" x-text="order(p.id)"></span>
                        </button>
                    </template>
                </div>
                <p x-show="note" x-text="note" class="mt-3 text-xs text-muted"></p>
            </section>

            <section class="card card-pad space-y-2.5">
                <button type="button" @click="download()" :disabled="busy" class="btn btn-dark h-11 w-full"><x-icon name="download" class="size-4"/> Download image</button>
                <button type="button" x-show="canShareFiles" @click="share()" :disabled="busy" class="btn btn-light h-11 w-full"><x-icon name="share" class="size-4"/> Share to Instagram, LinkedIn…</button>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="copyCaption()" class="btn btn-light h-10"><x-icon name="copy" class="size-4"/> Copy caption</button>
                    <button type="button" @click="linkedIn()" class="btn btn-light h-10"><svg viewBox="0 0 24 24" class="size-4" aria-hidden="true"><path fill="#0A66C2" d="M6.9 8.8H4.2V19h2.7zM5.6 4.6a1.6 1.6 0 1 0 0 3.2 1.6 1.6 0 0 0 0-3.2zM19.8 13.2c0-2.7-1.4-4.6-4-4.6a3.4 3.4 0 0 0-3.1 1.7V8.8h-2.6V19h2.7v-5.4c0-1.4.5-2.6 2-2.6s1.8 1.3 1.8 2.6V19h2.7z"/></svg> Post on LinkedIn</button>
                </div>
                <details class="text-sm">
                    <summary class="cursor-pointer text-muted hover:text-ink">See the caption</summary>
                    <textarea readonly rows="7" class="input mt-2 h-auto py-2 text-[13px] leading-relaxed" x-text="caption()" @focus="$el.select()"></textarea>
                </details>
                <div class="rounded-xl bg-zinc-50 p-3 text-xs leading-relaxed text-ink-soft">
                    <p class="font-semibold text-ink">How to post</p>
                    <p class="mt-1"><span class="font-medium">LinkedIn:</span> download the image, click “Post on LinkedIn”, add the image to your post and paste the caption.</p>
                    <p class="mt-1"><span class="font-medium">Instagram:</span> on your phone, tap “Share” (or download it) and choose Instagram. Put the link in your bio or story link sticker — Instagram doesn't make links in captions clickable.</p>
                </div>
            </section>
        </aside>
    </div>
</div>
</x-layouts.app>
