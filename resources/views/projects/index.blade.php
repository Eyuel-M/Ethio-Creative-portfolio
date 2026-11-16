<x-layouts.app title="Projects">
<section class="card card-pad"><h1 class="font-display text-3xl font-bold">Projects</h1><p class="mt-1 text-muted">Private workspaces created after both parties confirm an agreement.</p></section>
@if($projects->isEmpty())
    <x-empty icon="folder" title="No projects yet" text="Once you and the other party confirm the same agreement version, the workspace appears here."/>
@else
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">@foreach($projects as $p)<x-project-card :project="$p" :viewer="auth()->user()"/>@endforeach</div>
    {{ $projects->links() }}
@endif
</x-layouts.app>
