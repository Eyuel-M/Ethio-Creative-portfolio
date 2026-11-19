@props(['user'])
{{-- Profile-photo editor. Open from anywhere with: $dispatch('open-avatar-editor') --}}
<div x-data="{ open: false, preview: @js($user->avatarUrl()), picked: false, error: '',
              pick(e) {
                  const f = e.target.files[0]; this.error = '';
                  if (!f) return;
                  if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { this.error = 'Use a JPG, PNG or WebP image.'; e.target.value = ''; return; }
                  if (f.size > 5 * 1024 * 1024) { this.error = 'That image is over 5 MB.'; e.target.value = ''; return; }
                  this.preview = URL.createObjectURL(f); this.picked = true;
              } }"
     @open-avatar-editor.window="open = true" @keydown.escape.window="open = false">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center bg-black/40 sm:items-center sm:p-4" @click.self="open = false">
            <div x-show="open" x-transition role="dialog" aria-modal="true" aria-labelledby="avatar-editor-title" x-trap.noscroll="open"
                 class="w-full max-w-sm rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-center justify-between">
                    <h2 id="avatar-editor-title" class="font-display text-xl font-bold">Profile photo</h2>
                    <button type="button" @click="open = false" class="grid size-9 place-items-center rounded-full text-muted hover:bg-zinc-100 hover:text-ink" aria-label="Close"><x-icon name="x" class="size-5"/></button>
                </div>

                <form method="POST" action="{{ route('settings.avatar') }}" enctype="multipart/form-data" class="mt-6">@csrf
                    <label class="group relative mx-auto block size-40 cursor-pointer overflow-hidden rounded-full bg-indigo-100 ring-4 ring-white shadow-lg">
                        <img x-show="preview" :src="preview" alt="Preview of your profile photo" class="size-full object-cover">
                        <span x-show="!preview" class="grid size-full place-items-center font-display text-4xl font-bold text-indigo-700">{{ $user->initials() }}</span>
                        <span class="absolute inset-0 grid place-items-center bg-black/45 text-sm font-semibold text-white opacity-0 transition group-hover:opacity-100"><span class="flex flex-col items-center gap-1"><x-icon name="camera" class="size-6"/>Choose image</span></span>
                        <input id="avatar-editor-file" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)">
                    </label>
                    <p class="mt-4 text-center text-sm text-muted">JPG, PNG or WebP, up to 5 MB.</p>
                    <p x-show="error" x-text="error" class="error text-center" role="alert"></p>

                    <div class="mt-6 flex gap-2">
                        <label for="avatar-editor-file" class="btn btn-light flex-1 cursor-pointer"><x-icon name="upload" class="size-4"/> Choose image</label>
                        <button class="btn btn-dark flex-1" :disabled="!picked">Save</button>
                    </div>
                </form>
                @if($user->avatar_path)
                    <form method="POST" action="{{ route('settings.avatar') }}" class="mt-2" x-show="!picked">@csrf
                        <input type="hidden" name="remove" value="1">
                        <button class="btn btn-ghost w-full text-rose-600" onclick="return confirm('Remove your profile photo?')">Remove photo</button>
                    </form>
                @endif
            </div>
        </div>
    </template>
</div>
