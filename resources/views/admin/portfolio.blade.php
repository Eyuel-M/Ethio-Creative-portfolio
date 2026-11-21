<x-layouts.admin title="Portfolio review">
<x-admin.header title="Portfolio review" subtitle="Newly published projects. Approve good work, feature the best on the homepage, remove anything that breaks the rules.">
    <x-slot:actions>
        @if($tab === 'review' && $projects->isNotEmpty())
            <form method="POST" action="{{ route('admin.portfolio.approve-all') }}">@csrf @foreach($projects as $p)<input type="hidden" name="ids[]" value="{{ $p->id }}">@endforeach<button class="btn btn-sm btn-light"><x-icon name="check" class="size-4"/> Approve all on this page</button></form>
        @endif
    </x-slot:actions>
</x-admin.header>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['review' => ['To review', $counts['review']], 'approved' => ['Approved', $counts['approved']], 'featured' => ['Featured', $counts['featured']], 'removed' => ['Removed', $counts['removed']]]"/>
    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3 2xl:grid-cols-4">
        @forelse($projects as $p)
            <article class="overflow-hidden rounded-2xl border border-line">
                <a href="{{ route('portfolio.show', $p) }}" target="_blank" class="block aspect-[4/3] bg-zinc-100">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover" loading="lazy">@endif</a>
                <div class="p-4">
                    <p class="truncate font-medium">{{ $p->title }}</p>
                    <p class="truncate text-xs text-muted">by @if(auth()->user()->canAdmin('users'))<a href="{{ route('admin.users.show', $p->profile->user) }}" class="hover:underline">{{ $p->profile->user->name }}</a>@else{{ $p->profile->user->name }}@endif · {{ $p->category?->name }} · {{ $p->blocks_count }} blocks · {{ $p->published_at?->diffForHumans() }}</p>
                    @if($p->moderation_note)<p class="mt-2 rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs text-rose-700">{{ $p->moderation_note }}</p>@endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($p->status === 'removed')
                            <x-admin.action :action="route('admin.portfolio.moderate', $p)" :fields="['action' => 'restore']" label="Restore"/>
                        @else
                            @if(! $p->reviewed_at)<form method="POST" action="{{ route('admin.portfolio.moderate', $p) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-dark"><x-icon name="check" class="size-4"/> Approve</button></form>@endif
                            <form method="POST" action="{{ route('admin.portfolio.moderate', $p) }}">@csrf<input type="hidden" name="action" value="{{ $p->featured_at ? 'unfeature' : 'feature' }}"><button class="btn btn-sm btn-light"><x-icon name="star" class="size-4"/> {{ $p->featured_at ? 'Unfeature' : 'Feature' }}</button></form>
                            <x-admin.action :action="route('admin.portfolio.moderate', $p)" :fields="['action' => 'remove']" label="Remove" tone="danger" confirm="The freelancer is notified with your reason."/>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="col-span-full py-12 text-center text-sm text-muted">Nothing to review.</p>
        @endforelse
    </div>
</x-admin.panel>
{{ $projects->links() }}
</x-layouts.admin>
