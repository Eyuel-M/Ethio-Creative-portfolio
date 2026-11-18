<x-layouts.app title="Saved talent">
    <section class="card card-pad"><h1 class="font-display text-3xl font-bold">Saved talent</h1><p class="mt-1 text-muted">Your shortlist of creatives to compare and invite.</p></section>
    @if($profiles->isEmpty())
        <x-empty icon="bookmark" title="Your shortlist is empty" text="Save freelancers from the directory to compare them here."><a href="{{ route('freelancers.index') }}" class="btn btn-dark">Browse talent</a></x-empty>
    @else
        <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">@foreach($profiles as $p)<x-talent-card :profile="$p" :shortlisted="true"/>@endforeach</div>
        @if($profiles->hasPages())<div class="card card-pad">{{ $profiles->links() }}</div>@endif
    @endif
</x-layouts.app>
