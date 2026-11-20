@props(['url', 'title', 'image' => null, 'subtitle' => null, 'label' => 'Share', 'iconOnly' => false, 'pill' => false, 'square' => false])
{{-- Share popup: social platforms + copy link (+ native share sheet where available). --}}
@php
    $text = $title.' — '.config('marketplace.brand');
    $sid = 'share-link-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $e = fn ($v) => rawurlencode($v);
    $platforms = [
        ['Telegram', 'https://t.me/share/url?url='.$e($url).'&text='.$e($text), '#229ED9', '<path d="M21 4 3 11.2l5.6 2.1L10.8 20l3.3-4.2 5.1 3.9z" fill="currentColor"/><path d="m8.6 13.3 9.2-6.9-7 8.2" stroke="#fff" stroke-width="1.2" fill="none" stroke-linejoin="round"/>'],
        ['WhatsApp', 'https://wa.me/?text='.$e($text.' '.$url), '#25D366', '<path d="M12 3.2a8.8 8.8 0 0 0-7.6 13.2L3.2 20.8l4.5-1.2A8.8 8.8 0 1 0 12 3.2z" fill="currentColor"/><path d="M9 8.3c.2-.4.5-.4.8-.4h.5c.2 0 .4.1.5.4l.7 1.6c.1.3 0 .5-.1.7l-.5.6c-.1.1-.2.3 0 .5a6.3 6.3 0 0 0 2.8 2.4c.2.1.4.1.5-.1l.6-.7c.2-.2.4-.3.7-.2l1.6.8c.3.1.4.3.4.5 0 .5-.2 1.2-.7 1.6-.6.4-1.4.6-2.4.3a10 10 0 0 1-5.4-4.6c-.6-1.1-.6-2.2 0-3z" fill="#fff"/>'],
        ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u='.$e($url), '#1877F2', '<path d="M13.5 21v-7.2h2.4l.4-2.9h-2.8V9.1c0-.8.3-1.4 1.4-1.4h1.5V5.2a19 19 0 0 0-2.2-.1c-2.2 0-3.6 1.3-3.6 3.7v2.1H8.2v2.9h2.4V21z" fill="currentColor"/>'],
        ['LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url='.$e($url), '#0A66C2', '<path d="M6.9 8.8H4.2V19h2.7zM5.6 4.6a1.6 1.6 0 1 0 0 3.2 1.6 1.6 0 0 0 0-3.2zM19.8 13.2c0-2.7-1.4-4.6-4-4.6a3.4 3.4 0 0 0-3.1 1.7V8.8h-2.6V19h2.7v-5.4c0-1.4.5-2.6 2-2.6s1.8 1.3 1.8 2.6V19h2.7z" fill="currentColor"/>'],
        ['X', 'https://twitter.com/intent/tweet?url='.$e($url).'&text='.$e($text), '#0c0c10', '<path d="M17.3 4h2.9l-6.4 7.3 7.5 9.7h-5.9l-4.6-6-5.3 6H2.6l6.8-7.8L2.2 4h6l4.2 5.5zm-1 15.3h1.6L7.6 5.6H5.9z" fill="currentColor"/>'],
        ['Email', 'mailto:?subject='.$e($text).'&body='.$e($url), '#52525b', '<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m3.5 7 8.5 6 8.5-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'],
    ];
@endphp
<div x-data="{ open: false, copied: false, canNative: !!navigator.share,
              async copy() {
                  try { await navigator.clipboard.writeText(@js($url)); }
                  catch (e) { const el = this.$refs.link; el.select(); document.execCommand('copy'); }
                  this.copied = true; setTimeout(() => this.copied = false, 1800);
              },
              async native() { try { await navigator.share({ title: @js($title), url: @js($url) }); } catch (e) {} } }"
     @keydown.escape.window="open = false" {{ $attributes->merge(['class' => 'inline-flex']) }}>
    <button type="button" @click="open = true" class="{{ $square ? 'btn btn-light size-12 px-0' : ($iconOnly ? 'btn btn-sm btn-light w-9 px-0' : ($pill ? 'btn btn-light btn-pill h-11 px-5' : 'btn btn-light')) }}" aria-haspopup="dialog" :aria-expanded="open" @if($iconOnly || $square) aria-label="{{ $label }}" title="{{ $label }}" @endif>
        <x-icon name="share" class="{{ $square ? 'size-5' : 'size-4' }}"/>@unless($iconOnly || $square) {{ $label }}@endunless
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" x-transition.opacity @click.self="open = false">
            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                 role="dialog" aria-modal="true" aria-label="Share {{ $title }}" x-trap.noscroll="open"
                 class="w-full max-w-md rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-3xl sm:p-6" @click.outside="open = false">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold">Share</h2>
                    <button type="button" @click="open = false" class="grid size-9 place-items-center rounded-full text-muted hover:bg-zinc-100 hover:text-ink" aria-label="Close"><x-icon name="x" class="size-5"/></button>
                </div>

                <div class="mt-4 flex items-center gap-3 rounded-2xl bg-zinc-50 p-3">
                    @if($image)
                        <img src="{{ $image }}" alt="" class="size-14 shrink-0 rounded-xl object-cover">
                    @else
                        <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-ink text-white"><x-icon name="layers" class="size-6"/></span>
                    @endif
                    <div class="min-w-0"><p class="truncate font-semibold">{{ $title }}</p>@if($subtitle)<p class="truncate text-sm text-muted">{{ $subtitle }}</p>@endif</div>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-2 sm:grid-cols-6">
                    @foreach($platforms as [$name, $href, $color, $svg])
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="group flex flex-col items-center gap-1.5 rounded-2xl p-2 text-xs font-medium text-ink-soft hover:bg-zinc-50 hover:text-ink">
                            <span class="grid size-12 place-items-center rounded-full text-white transition group-hover:scale-105" style="background: {{ $color }}"><svg viewBox="0 0 24 24" class="size-6" aria-hidden="true">{!! $svg !!}</svg></span>
                            {{ $name }}
                        </a>
                    @endforeach
                </div>

                <label class="label mt-5" for="{{ $sid }}">Link</label>
                <div class="flex items-center gap-2 rounded-xl border border-line bg-zinc-50 p-1.5 pl-3.5">
                    <input id="{{ $sid }}" x-ref="link" readonly value="{{ $url }}" class="min-w-0 flex-1 bg-transparent text-sm text-ink-soft outline-none" @focus="$el.select()">
                    <button type="button" @click="copy()" class="btn btn-sm shrink-0" :class="copied ? 'bg-leaf text-white' : 'btn-dark'">
                        <x-icon name="copy" class="size-4" x-show="!copied"/><x-icon name="check" class="size-4" x-show="copied" x-cloak/>
                        <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                    </button>
                </div>
                <button type="button" x-show="canNative" x-cloak @click="native()" class="btn btn-ghost mt-3 w-full"><x-icon name="more" class="size-4 rotate-90"/> More options</button>
            </div>
        </div>
    </template>
</div>
