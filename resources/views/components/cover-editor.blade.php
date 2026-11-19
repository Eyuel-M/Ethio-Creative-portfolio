@props(['profile', 'fallback' => null])
{{-- Profile-banner editor with drag-to-position. Open with: $dispatch('open-cover-editor') --}}
@php $current = $profile->coverUrl(); @endphp
<div x-data="{ open: false, preview: @js($current), pos: {{ (int) ($profile->cover_position ?? 50) }}, startPos: 0, startY: 0, dragging: false,
              picked: false, moved: false, error: '',
              pick(e) {
                  const f = e.target.files[0]; this.error = '';
                  if (!f) return;
                  if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { this.error = 'Use a JPG, PNG or WebP image.'; e.target.value = ''; return; }
                  if (f.size > 10 * 1024 * 1024) { this.error = 'That image is over 10 MB.'; e.target.value = ''; return; }
                  this.preview = URL.createObjectURL(f); this.picked = true; this.pos = 50;
              },
              overflow() {
                  const img = this.$refs.img, fr = this.$refs.frame;
                  if (!img || !img.naturalWidth) return 0;
                  const scale = Math.max(fr.clientWidth / img.naturalWidth, fr.clientHeight / img.naturalHeight);
                  return img.naturalHeight * scale - fr.clientHeight;
              },
              down(e) { if (!this.preview) return; this.dragging = true; this.startY = e.clientY; this.startPos = this.pos; e.currentTarget.setPointerCapture(e.pointerId); },
              move(e) {
                  if (!this.dragging) return;
                  const o = this.overflow(); if (o <= 1) return;
                  this.pos = Math.round(Math.min(100, Math.max(0, this.startPos - (e.clientY - this.startY) / o * 100)));
                  this.moved = true;
              },
              nudge(d) { if (!this.preview) return; this.pos = Math.min(100, Math.max(0, this.pos + d)); this.moved = true; } }"
     @open-cover-editor.window="open = true" @keydown.escape.window="open = false">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center bg-black/40 sm:items-center sm:p-4" @click.self="open = false">
            <div x-show="open" x-transition role="dialog" aria-modal="true" aria-labelledby="cover-editor-title" x-trap.noscroll="open"
                 class="w-full max-w-3xl rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-center justify-between">
                    <h2 id="cover-editor-title" class="font-display text-xl font-bold">Profile cover</h2>
                    <button type="button" @click="open = false" class="grid size-9 place-items-center rounded-full text-muted hover:bg-zinc-100 hover:text-ink" aria-label="Close"><x-icon name="x" class="size-5"/></button>
                </div>

                <form method="POST" action="{{ route('settings.cover') }}" enctype="multipart/form-data" class="mt-5">@csrf
                    <input type="hidden" name="cover_position" :value="pos">
                    {{-- Same shape as the banner on the profile, so what you see is what you get --}}
                    <div x-ref="frame" tabindex="0" role="slider" aria-label="Cover position — drag or use the arrow keys" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="pos"
                         @pointerdown="down($event)" @pointermove="move($event)" @pointerup="dragging = false" @pointercancel="dragging = false"
                         @keydown.arrow-up.prevent="nudge(-2)" @keydown.arrow-down.prevent="nudge(2)"
                         class="relative aspect-[5/1] touch-none select-none overflow-hidden rounded-xl bg-gradient-to-br from-indigo-200 via-violet-100 to-sky-200 outline-none focus-visible:ring-2 focus-visible:ring-ink"
                         :class="preview ? (dragging ? 'cursor-grabbing' : 'cursor-grab') : ''">
                        <img x-ref="img" x-show="preview" :src="preview" alt="" draggable="false" class="pointer-events-none size-full object-cover" :style="`object-position: 50% ${pos}%`">
                        <div x-show="preview" class="pointer-events-none absolute inset-x-0 bottom-2 flex justify-center transition" :class="dragging && 'opacity-0'">
                            <span class="flex items-center gap-1.5 rounded-full bg-black/55 px-3 py-1 text-xs font-medium text-white backdrop-blur"><x-icon name="swap" class="size-3.5 rotate-90"/> Drag to reposition</span>
                        </div>
                        <label x-show="!preview" for="cover-editor-file" class="absolute inset-0 grid cursor-pointer place-items-center text-center text-sm font-medium text-ink-soft">
                            <span><x-icon name="image" class="mx-auto size-7"/>Choose a cover image</span>
                        </label>
                    </div>
                    <p class="mt-3 text-sm text-muted">
                        @if(! $current && $fallback)Right now your profile shows your first project's cover. @endif
                        JPG, PNG or WebP, up to 10 MB. Wide images (about 1600 × 400) work best.
                    </p>
                    <p x-show="error" x-text="error" class="error" role="alert"></p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <label class="btn btn-light cursor-pointer"><x-icon name="upload" class="size-4"/> <span x-text="preview ? 'Replace image' : 'Choose image'">Choose image</span>
                            <input id="cover-editor-file" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)">
                        </label>
                        <button class="btn btn-dark ml-auto" :disabled="!(picked || (moved && preview))">Save cover</button>
                    </div>
                </form>
                @if($current)
                    <form method="POST" action="{{ route('settings.cover') }}" class="mt-2 text-right" x-show="!picked">@csrf
                        <input type="hidden" name="remove" value="1">
                        <button class="btn btn-ghost btn-sm text-rose-600" onclick="return confirm('Remove your cover image?')">Remove cover</button>
                    </form>
                @endif
            </div>
        </div>
    </template>
</div>
