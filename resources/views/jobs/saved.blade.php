<x-layouts.app title="Saved jobs">
    <section class="card card-pad"><h1 class="font-display text-3xl font-bold">Saved jobs</h1><p class="mt-1 text-muted">Projects you bookmarked to come back to.</p></section>
    @if($jobs->isEmpty())
        <x-empty icon="bookmark" title="No saved jobs yet" text="Tap the bookmark on any job to keep it here."><a href="{{ route('jobs.index') }}" class="btn btn-dark">Find projects</a></x-empty>
    @else
        <div class="grid gap-4 xl:grid-cols-2">@foreach($jobs as $job)<x-job-card :job="$job" :saved="true"/>@endforeach</div>
        <div class="card card-pad">{{ $jobs->links() }}</div>
    @endif
</x-layouts.app>
