@php $sec = fn ($s) => $s >= 60 ? floor($s / 60).'m '.($s % 60).'s' : $s.'s'; @endphp
<x-layouts.admin title="Traffic & sources">
<x-admin.header title="Traffic & sources" subtitle="Who visits, how they found us, and how far they go. Anonymous and first-party — no IP addresses are stored."/>
@include('admin.insights._nav')

<div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-6">
    <x-admin.stat label="Visitors" :value="number_format($t['visitors'])" :delta="$d('visitors')" :hint="number_format($t['new_visitors']).' first-time visits'"/>
    <x-admin.stat label="Visits" :value="number_format($t['sessions'])" :delta="$d('sessions')"/>
    <x-admin.stat label="Page views" :value="number_format($t['pageviews'])" :delta="$d('pageviews')"/>
    <x-admin.stat label="Pages per visit" :value="$t['pages_per_session']" :delta="$d('pages_per_session')"/>
    <x-admin.stat label="Avg. time engaged" :value="$sec($t['engaged'])" :delta="$d('engaged')" hint="per visit"/>
    <x-admin.stat label="Left after 1 page" :value="$t['bounce'].'%'" :delta="$t['prev']['bounce'] ? -\App\Support\Insights::change($t['bounce'], $t['prev']['bounce']) : null" hint="lower is better"/>
</div>

<x-admin.panel title="Visitors per day" :subtitle="'Unique visitors · last '.$days.' days'"><x-admin.columns :series="$daily" label="Visitors"/></x-admin.panel>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
    <x-admin.panel title="How visitors found us" subtitle="Visits by source"><x-admin.share :parts="$sources"/></x-admin.panel>
    <x-admin.panel title="Which sources bring engaged people" subtitle="Quality of each source — not just volume" :pad="false">
        <div class="overflow-x-auto"><table class="w-full min-w-[560px] text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Source</th><th class="px-3 py-3 text-right">Visits</th><th class="px-3 py-3 text-right">Pages/visit</th><th class="px-3 py-3 text-right">Time</th><th class="px-3 py-3 text-right">Left after 1 page</th><th class="px-3 py-3 text-right">Sign-ups</th><th class="px-3 py-3 text-right">Sign-up rate</th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach($quality as $q)
                    <tr><td class="px-5 py-2.5 font-medium">{{ $q['source'] }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($q['visits']) }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $q['pages'] }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $sec($q['engaged']) }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $q['bounce'] }}%</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $q['signups'] }}</td><td class="px-3 py-2.5 text-right font-semibold tabular-nums">{{ $q['conversion'] }}%</td></tr>
                @endforeach
            </tbody>
        </table></div>
    </x-admin.panel>
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    <x-admin.panel title="Social media" subtitle="Visits per platform"><x-admin.bars :rows="$social" empty="No social visits yet."/></x-admin.panel>
    <x-admin.panel title="Search engines" subtitle="Visits per engine"><x-admin.bars :rows="$search" empty="No search visits yet."/></x-admin.panel>
    <x-admin.panel title="Other websites" subtitle="Sites that link to us"><x-admin.bars :rows="$referral" empty="No referring sites yet."/></x-admin.panel>
</div>

@if($campaigns)
    <x-admin.panel title="Campaigns" subtitle="Links tagged with ?utm_campaign= (add these to your ads, posts and emails)"><x-admin.bars :rows="$campaigns"/></x-admin.panel>
@endif

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="Where visits start" subtitle="Landing pages"><x-admin.bars :rows="$landing"/></x-admin.panel>
    <x-admin.panel title="Most viewed pages" subtitle="Views · avg time · avg scroll depth"><x-admin.bars :rows="$pages"/></x-admin.panel>
</div>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="How deep visits go" subtitle="Pages viewed per visit"><x-admin.bars :rows="$depth"/></x-admin.panel>
    <x-admin.panel title="How far pages are read" subtitle="Scroll depth per page view"><x-admin.bars :rows="$scroll"/></x-admin.panel>
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    <x-admin.panel title="Devices"><x-admin.bars :rows="$devices"/></x-admin.panel>
    <x-admin.panel title="Browsers"><x-admin.bars :rows="$browsers"/></x-admin.panel>
    <x-admin.panel title="Countries" subtitle="Available once the site runs behind Cloudflare"><x-admin.bars :rows="$countries" empty="No country data yet."/></x-admin.panel>
</div>
</x-layouts.admin>
