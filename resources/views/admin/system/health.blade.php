<x-layouts.admin title="System health">
<x-admin.header title="System health" subtitle="Background work, payments, uploads and server settings — the things that break quietly."/>
<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
    <x-admin.panel title="Checks" :pad="false">
        <ul class="divide-y divide-line">
            @foreach($checks as [$label, $ok, $detail])
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full {{ $ok ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}"><x-icon :name="$ok ? 'check' : 'x'" class="size-4"/></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-medium">{{ $label }}</span><span class="block text-xs text-muted">{{ $detail }}</span></span>
                    <span class="text-xs font-semibold {{ $ok ? 'text-emerald-700' : 'text-rose-700' }}">{{ $ok ? 'OK' : 'Needs attention' }}</span>
                </li>
            @endforeach
        </ul>
    </x-admin.panel>
    <x-admin.panel title="Environment">
        <dl class="space-y-2 text-sm">@foreach($info as $k => $v)<div class="flex justify-between gap-3"><dt class="text-muted">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>@endforeach</dl>
    </x-admin.panel>
</div>
@if($failedJobs->isNotEmpty())
    <x-admin.panel title="Recent failed background jobs" subtitle="Retry with: php artisan queue:retry all" :pad="false">
        <ul class="divide-y divide-line">@foreach($failedJobs as $j)<li class="px-5 py-3 text-sm"><p class="tabular-nums text-xs">{{ \Illuminate\Support\Str::limit(json_decode($j->payload)->displayName ?? 'job', 80) }}</p><p class="truncate text-xs text-rose-700">{{ \Illuminate\Support\Str::limit(strtok($j->exception, "\n"), 160) }}</p><p class="text-xs text-muted">{{ $j->failed_at }}</p></li>@endforeach</ul>
    </x-admin.panel>
@endif
</x-layouts.admin>
