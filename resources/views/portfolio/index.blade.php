<x-layouts.app title="My Portfolio">
<section class="card card-pad flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl font-bold">My Portfolio</h1>
        <p class="mt-1 text-muted">Structured case studies are what clients look at first.</p>
    </div>
    <div class="flex items-center gap-4">
        <div class="w-44">
            <div class="flex justify-between text-xs"><span class="text-muted">Projects used</span><span class="font-semibold">{{ $projects->count() }} / {{ $limit }}</span></div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full {{ $projects->count() >= $limit ? 'bg-amber-500' : 'bg-leaf' }}" style="width: {{ min(100, $projects->count() / max(1, $limit) * 100) }}%"></div></div>
        </div>
        @if($projects->count() < $limit)
            <a href="{{ route('portfolio.create') }}" class="btn btn-dark"><x-icon name="plus" class="size-4"/> New project</a>
        @else
            <a href="{{ route('billing.index') }}" class="btn btn-brand"><x-icon name="rocket" class="size-4"/> Upgrade for more</a>
        @endif
    </div>
</section>

@if($projects->isEmpty())
    <x-empty icon="layers" title="Start your first case study" text="Add a cover, a few images and the story behind the work. Clients evaluate quality before reaching out.">
        <a href="{{ route('portfolio.create') }}" class="btn btn-dark">Create project</a>
    </x-empty>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($projects as $p)
            <article class="card overflow-hidden">
                <a href="{{ route('portfolio.edit', $p) }}" class="block aspect-[4/3] bg-zinc-100">
                    @if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->cover_alt }}" class="size-full object-cover" loading="lazy">@else<div class="grid size-full place-items-center text-faint"><x-icon name="image" class="size-10"/></div>@endif
                </a>
                <div class="p-5">
                    <div class="flex items-center justify-between"><x-status :status="$p->status"/><span class="flex items-center gap-1 text-xs text-muted"><x-icon name="eye" class="size-4"/>{{ $p->views }}</span></div>
                    <h2 class="mt-3 font-semibold">{{ $p->title }}</h2>
                    <p class="mt-1 text-xs text-muted">{{ $p->blocks_count }} content blocks · updated {{ $p->updated_at->diffForHumans() }}</p>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('portfolio.edit', $p) }}" class="btn btn-sm btn-light flex-1"><x-icon name="pen" class="size-4"/> Edit</a>
                        <a href="{{ route('portfolio.show', $p) }}" class="btn btn-sm btn-light" aria-label="Preview"><x-icon name="eye" class="size-4"/></a>
                        <form method="POST" action="{{ route('portfolio.publish', $p) }}">@csrf<button class="btn btn-sm {{ $p->isPublished() ? 'btn-light' : 'btn-dark' }}">{{ $p->isPublished() ? 'Unpublish' : 'Publish' }}</button></form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
</x-layouts.app>
