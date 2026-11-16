@if ($paginator->hasPages())
<nav class="flex items-center justify-between gap-3 pt-2" aria-label="Pagination">
    <p class="text-sm text-muted">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
    <div class="flex items-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm btn-light opacity-40"><x-icon name="chevron-left" class="size-4"/></span>
        @else
            <a class="btn btn-sm btn-light" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><x-icon name="chevron-left" class="size-4"/></a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="px-2 text-muted">…</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <a href="{{ $url }}" class="btn btn-sm {{ $page == $paginator->currentPage() ? 'btn-dark' : 'btn-ghost' }} hidden sm:inline-flex" @if($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a class="btn btn-sm btn-light" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><x-icon name="chevron-right" class="size-4"/></a>
        @else
            <span class="btn btn-sm btn-light opacity-40"><x-icon name="chevron-right" class="size-4"/></span>
        @endif
    </div>
</nav>
@endif
