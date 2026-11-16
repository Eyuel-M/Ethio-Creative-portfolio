<div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
    <div class="flex items-center gap-4 text-sm"><x-icon name="grid" class="size-4 text-ink-soft"/><x-icon name="star" class="size-4 text-ink-soft"/><span class="text-muted">Dashboards</span><span class="text-faint">/</span><span>{{ ucfirst(auth()->user()->workspace()) }}</span></div>
    <div class="flex items-center gap-3">
        <form action="{{ auth()->user()->workspace() === 'client' ? route('freelancers.index') : route('jobs.index') }}" class="hidden sm:block"><label class="flex h-10 w-64 items-center gap-2 rounded-xl border border-line bg-white px-3 text-sm"><x-icon name="search" class="size-4 text-muted"/><span class="sr-only">Search</span><input name="q" class="w-full outline-none placeholder:text-faint" placeholder="{{ auth()->user()->workspace() === 'client' ? 'Search talent' : 'Search projects' }}"></label></form>
        <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 hover:bg-zinc-100" aria-label="Notifications"><x-icon name="bell" class="size-5"/>@if($navCounts['notifications'] ?? 0)<span class="absolute right-1.5 top-1.5 size-2 rounded-full bg-leaf"></span>@endif</a>
    </div>
</div>
