<x-layouts.admin title="Content & behaviour">
<x-admin.header title="Content & behaviour" subtitle="What people look at, search for and do — the interactions that show what's working."/>
@include('admin.insights._nav')

<x-admin.panel title="Top portfolio projects" subtitle="Views, unique viewers, time on page, how far they scrolled, appreciations and shares" :pad="false">
    <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Project</th><th class="px-3 py-3 text-right">Views</th><th class="px-3 py-3 text-right">People</th><th class="px-3 py-3 text-right">Avg time</th><th class="px-3 py-3 text-right">Scrolled</th><th class="px-3 py-3 text-right">Appreciations</th><th class="px-3 py-3 text-right">Shares</th><th class="px-3 py-3 text-right">Appreciation rate</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($projects as $p)
                <tr class="hover:bg-zinc-50"><td class="px-5 py-2.5"><a href="{{ route('portfolio.show', $p->id) }}" target="_blank" class="font-medium hover:underline">{{ $p->title }}</a><span class="block text-xs text-muted">{{ $p->by }}</span></td>
                    <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($p->views) }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($p->people) }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $p->time }}s</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $p->scroll }}%</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $p->likes }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $p->shares }}</td>
                    <td class="px-3 py-2.5 text-right font-semibold tabular-nums">{{ $p->people ? round($p->likes / $p->people * 100, 1) : 0 }}%</td></tr>
            @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-muted">No project views in this period.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-admin.panel>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="Most viewed freelancers" subtitle="Profile views · unique people">
        <x-admin.bars :rows="$profiles->map(fn ($r) => ['label' => $profileNames[$r->subject_id] ?? 'Deleted', 'value' => $r->n, 'extra' => $r->u.' people'])->all()" empty="No profile views yet."/>
    </x-admin.panel>
    <x-admin.panel title="Jobs: viewed → applied" subtitle="Views · application starts · submitted">
        <x-admin.bars :rows="$jobViews->map(fn ($r) => ['label' => $jobNames[$r->subject_id] ?? 'Deleted job', 'value' => $r->n, 'extra' => ($applyStarts[$r->subject_id] ?? 0).' started · '.($jobsAppsDone[$r->subject_id] ?? 0).' sent'])->all()" empty="No job views yet."/>
    </x-admin.panel>
</div>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="What people search for" subtitle="Top searches across work, talent and jobs"><x-admin.bars :rows="$queries" empty="No searches yet."/></x-admin.panel>
    <x-admin.panel title="Searches with no results" subtitle="Demand we can't serve yet — ideas for categories, skills or recruiting"><x-admin.bars :rows="$zero" color="#eb6834" empty="Every search found something."/></x-admin.panel>
</div>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
    <x-admin.panel title="Interest by category" subtitle="Views of work, profiles and jobs"><x-admin.bars :rows="$categories"/></x-admin.panel>
    <x-admin.panel title="Shared to" subtitle="Share-menu clicks by platform"><x-admin.bars :rows="$sharePlatforms" empty="Nothing shared yet."/></x-admin.panel>
    <x-admin.panel title="Outside links clicked" subtitle="Where people leave to (portfolios, socials)"><x-admin.bars :rows="$outbound" empty="No outbound clicks yet."/></x-admin.panel>
</div>

<x-admin.panel title="Every interaction" subtitle="All tracked actions in this period. Download the raw rows from Data export." :pad="false">
    <div class="overflow-x-auto"><table class="w-full min-w-[560px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Interaction</th><th class="px-3 py-3 tabular-nums normal-case">event</th><th class="px-3 py-3 text-right">Times</th><th class="px-3 py-3 text-right">People</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($events as $e)
                <tr><td class="px-5 py-2.5">{{ $e->label }}</td><td class="px-3 py-2.5 tabular-nums text-xs text-muted">{{ $e->name }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($e->n) }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($e->u) }}</td></tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-muted">No interactions recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-admin.panel>
</x-layouts.admin>
