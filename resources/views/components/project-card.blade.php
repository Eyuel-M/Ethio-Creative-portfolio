@props(['project', 'viewer'])
@php
    $isClient = $project->client_user_id === $viewer->id;
    $other = $isClient ? $project->freelancer : $project->client;
    $pct = $project->progress();
    $badge = match ($project->status) { 'in_progress' => ['On Track', 'bg-leaf text-white'], 'revision_requested' => ['Revisions', 'bg-amber-100 text-amber-800'], 'cancellation_requested' => ['At Risk', 'bg-rose-500 text-white'], 'submitted_for_review', 'delivered' => ['In Review', 'bg-brand-soft text-brand'], default => [\App\Support\Labels::status($project->status), 'bg-zinc-100 text-ink-soft'] };
    $overdue = $project->agreement?->deadline?->isPast() && ! in_array($project->status, ['completed', 'cancelled']);
    if ($overdue) { $badge = ['Delayed', 'bg-rose-500 text-white']; }
@endphp
<a href="{{ route('projects.show', $project) }}" class="card block p-5 transition hover:shadow-[0_10px_30px_-18px_rgba(12,12,16,.35)]">
    <div class="flex items-start justify-between gap-3">
        <p class="font-semibold leading-snug">{{ $project->title }}</p>
        <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-medium {{ $badge[1] }}">{{ $badge[0] }}</span>
    </div>
    <p class="mt-2 flex items-center gap-1.5 text-sm text-ink-soft"><x-icon name="user" class="size-4"/>{{ $isClient ? $other->name : ($other->clientProfile?->displayName() ?? $other->name) }}</p>
    <div class="mt-5 flex justify-between text-sm"><span class="text-muted">Progress</span><span class="font-medium text-leaf-dark">{{ $pct }}%</span></div>
    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-gradient-to-r from-brand to-[#79a3ff]" style="width: {{ max(3, $pct) }}%"></div></div>
    <div class="mt-5 flex justify-between text-sm text-muted">
        <span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4"/>{{ $project->agreement?->deadline?->format('M j, Y') }}</span>
        <span class="font-medium text-ink">{{ \App\Support\Money::format($project->agreement?->amount_minor, $project->agreement?->currency ?? 'ETB') }}</span>
    </div>
</a>
