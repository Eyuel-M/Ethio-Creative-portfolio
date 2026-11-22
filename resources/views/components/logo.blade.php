@props(['dark' => false])
<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="relative grid size-9 place-items-center rounded-xl bg-ink text-white shadow-[0_6px_14px_-6px_rgba(12,12,16,.6)]">
        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 7h10M7 12h7M7 17h10"/><circle cx="18" cy="12" r="1.6" fill="#34c759" stroke="none"/></svg>
    </span>
    <span class="font-display text-[19px] font-bold tracking-tight">Dezigners<span class="text-muted font-semibold">Hub</span><span class="text-leaf">.</span></span>
</a>
