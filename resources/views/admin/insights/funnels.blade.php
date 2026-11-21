<x-layouts.admin title="Funnels">
<x-admin.header title="Funnels" subtitle="The same people, step by step — each step only counts those who also reached the one before. The biggest drop is where to improve first."/>
@include('admin.insights._nav')
<x-admin.panel title="From visit to sign-up" subtitle="Visitors in this period"><x-admin.funnel :steps="$site"/></x-admin.panel>
<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="Freelancer journey" subtitle="Freelancers who joined in this period"><x-admin.funnel :steps="$freelancer" color="#1baf7a"/></x-admin.panel>
    <x-admin.panel title="Client journey" subtitle="Clients who joined in this period"><x-admin.funnel :steps="$client" color="#6250d6"/></x-admin.panel>
</div>
<x-admin.panel title="Sign-ups by source" subtitle="Which channels bring members, not just visitors">
    <x-admin.bars :rows="collect($quality)->map(fn ($q) => ['label' => $q['source'], 'value' => $q['signups'], 'extra' => $q['conversion'].'% of '.number_format($q['visits']).' visits'])->sortByDesc('value')->values()->all()" empty="No sign-ups in this period."/>
</x-admin.panel>
</x-layouts.admin>
