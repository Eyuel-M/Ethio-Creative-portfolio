{{-- Contents list for a post: numbered sections, the current one in the post's accent, finished ones ticked. --}}
@php $accent = \App\Models\Post::ACCENTS[$post->opt('accent')]; @endphp
<ol class="space-y-0.5 p-2">
    @foreach($items as $i => $h)
        <li>
            <a href="#{{ $h['id'] }}" @click.prevent="go(@js($h['id']))"
               class="group/t flex items-start gap-2.5 rounded-xl px-2.5 py-2 text-[13px] leading-snug transition {{ $h['n'] ? '' : 'pl-[42px]' }}"
               :class="active === @js($h['id']) ? '{{ $accent['soft'] }} font-semibold text-ink' : (index > {{ $i }} ? 'text-ink-soft hover:bg-zinc-50' : 'text-muted hover:bg-zinc-50 hover:text-ink')">
                @if($h['n'])
                    <span class="mt-px grid h-[18px] min-w-[22px] shrink-0 place-items-center rounded-md text-[10px] font-bold tabular-nums transition"
                          :class="active === @js($h['id']) ? '{{ $accent['bg'] }} text-white' : (index > {{ $i }} ? '{{ $accent['soft'] }} {{ $accent['text'] }}' : 'bg-zinc-100 text-muted group-hover/t:text-ink')">
                        <span x-show="!(index > {{ $i }} && active !== @js($h['id']))">{{ $h['n'] }}</span>
                        <svg x-show="index > {{ $i }} && active !== @js($h['id'])" x-cloak class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                    </span>
                @endif
                <span class="min-w-0">{{ $h['text'] }}</span>
            </a>
        </li>
    @endforeach
</ol>
