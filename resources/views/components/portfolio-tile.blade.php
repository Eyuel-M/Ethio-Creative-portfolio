@props(['project', 'showAuthor' => true])
<a href="{{ route('portfolio.show', $project) }}" class="group relative block overflow-hidden rounded-2xl bg-zinc-900">
    <div class="aspect-[4/3]">
        @if($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="{{ $project->cover_alt ?? $project->title }}" class="size-full object-cover transition duration-500 group-hover:scale-[1.04]" loading="lazy">
        @else
            <div class="grid size-full place-items-center bg-gradient-to-br from-zinc-800 to-zinc-900 text-zinc-500"><x-icon name="image" class="size-10"/></div>
        @endif
    </div>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-black/10"></div>
    <span class="absolute left-3 top-3 grid size-8 place-items-center rounded-full bg-white/90 text-ink"><x-icon name="heart" class="size-4"/></span>
    @if($project->category)<span class="absolute right-3 top-3 rounded-full bg-black/55 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur">{{ $project->category->name }}</span>@endif
    <div class="absolute inset-x-0 bottom-0 p-4 text-white">
        <p class="font-display text-[15px] font-semibold leading-snug">{{ $project->title }}</p>
        @if($showAuthor)
            <div class="mt-2 flex items-center gap-2 text-[13px] text-white/85"><x-avatar :user="$project->profile->user" size="size-6" class="text-[10px]"/> {{ $project->profile->user->displayName() }}</div>
        @endif
    </div>
</a>
