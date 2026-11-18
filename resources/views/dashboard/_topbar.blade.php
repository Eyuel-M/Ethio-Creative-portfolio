<div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
    <div class="flex items-center gap-4 text-sm"><x-icon name="grid" class="size-4 text-ink-soft"/><x-icon name="star" class="size-4 text-ink-soft"/><span class="text-muted">Dashboards</span><span class="text-faint">/</span><span>{{ ucfirst(auth()->user()->workspace()) }}</span></div>
    <span class="text-xs text-muted">{{ now()->timezone(auth()->user()->timezone ?? 'Africa/Addis_Ababa')->format('l, F j') }}</span>
</div>
