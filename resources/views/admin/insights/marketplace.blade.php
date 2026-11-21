<x-layouts.admin title="Marketplace health">
<x-admin.header title="Marketplace health" subtitle="Supply and demand by category, how fast jobs get answered, and whether members come back."/>
@include('admin.insights._nav')
<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach($kpis as [$k, $v])<x-admin.stat :label="$k" :value="$v"/>@endforeach
</div>
<x-admin.panel title="Supply & demand by category" :subtitle="'Freelancers and published work right now · jobs, applications and hires in the last '.$days.' days'" :pad="false">
    <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Category</th><th class="px-3 py-3 text-right">Freelancers</th><th class="px-3 py-3 text-right">Published work</th><th class="px-3 py-3 text-right">Jobs</th><th class="px-3 py-3 text-right">Applications</th><th class="px-3 py-3 text-right">Per job</th><th class="px-3 py-3 text-right">Freelancers per job</th><th class="px-3 py-3 text-right">Hires</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($rows as $r)
                <tr><td class="px-5 py-2.5 font-medium">{{ $r->name }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $r->freelancers }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $r->work }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $r->jobs }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $r->apps }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $r->per_job ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">@if($r->ratio !== null)<span class="{{ $r->ratio < 1 ? 'font-semibold text-amber-700' : '' }}">{{ $r->ratio }}</span>@else — @endif</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $r->hires }}</td></tr>
            @endforeach
        </tbody>
    </table></div>
    <p class="border-t border-line px-5 py-3 text-xs text-muted">Fewer than 1 freelancer per job (amber) means demand outpaces supply — recruit creatives there. Many freelancers with few jobs means you should win more clients in that category.</p>
</x-admin.panel>
<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="New members per day"><x-admin.columns :series="$signups" label="Sign-ups"/></x-admin.panel>
    <x-admin.panel title="Jobs published per day"><x-admin.columns :series="$jobsDaily" label="Jobs" color="#6250d6"/></x-admin.panel>
</div>
</x-layouts.admin>
