<x-layouts.admin title="Dashboard">
<x-admin.header title="Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}" subtitle="What needs attention, and how the marketplace is doing over the last 30 days.">
    <x-slot:actions>
        @if(auth()->user()->canAdmin('insights'))<a href="{{ route('admin.insights.traffic') }}" class="btn btn-light btn-sm"><x-icon name="chart" class="size-4"/> Full insights</a>@endif
    </x-slot:actions>
</x-admin.header>

{{-- Needs attention --}}
<section>
    <h2 class="mb-3 text-[13px] font-semibold uppercase tracking-wider text-muted">Needs attention</h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($queues as [$label, $n, $age, $route, $section, $icon])
            <a href="{{ route($route) }}" class="group flex items-center gap-4 rounded-2xl border bg-white p-4 transition hover:shadow-sm {{ $n ? 'border-line hover:border-zinc-300' : 'border-line opacity-70' }}">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $n ? 'bg-ink text-white' : 'bg-zinc-100 text-muted' }}"><x-icon :name="$icon" class="size-5"/></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium">{{ $label }}</span>
                    <span class="block text-xs text-muted">{{ $n ? ($age ? 'Oldest waiting '.$age : 'Open the queue') : 'All clear' }}</span>
                </span>
                <span class="font-display text-2xl font-bold tabular-nums {{ $n ? '' : 'text-faint' }}">{{ $n }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- KPIs --}}
<section>
    <h2 class="mb-3 text-[13px] font-semibold uppercase tracking-wider text-muted">Last 30 days <span class="font-normal normal-case tracking-normal">· change vs the 30 days before</span></h2>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-6">
        @foreach($kpis as [$label, $value, $delta, $route, $section])
            <x-admin.stat :label="$label" :value="$value" :delta="$delta" :href="auth()->user()->canAdmin($section) ? route($route) : null"/>
        @endforeach
    </div>
</section>

{{-- Members by path --}}
<section>
    <h2 class="mb-3 text-[13px] font-semibold uppercase tracking-wider text-muted">Members <span class="font-normal normal-case tracking-normal">· which path they chose</span></h2>
    @php $canUsers = auth()->user()->canAdmin('users'); @endphp
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        <x-admin.stat label="Freelancers" :value="number_format($members['freelancer'])" :href="$canUsers ? route('admin.users', ['role' => 'freelancer']) : null"/>
        <x-admin.stat label="Clients" :value="number_format($members['client'])" :href="$canUsers ? route('admin.users', ['role' => 'client']) : null"/>
        <x-admin.stat label="Undecided" :value="number_format($members['member'])" :hint="number_format($members['member_active']).' browsed in the last 7 days'" :href="$canUsers ? route('admin.users', ['role' => 'member', 'sort' => 'last_seen']) : null" class="col-span-2 md:col-span-1"/>
    </div>
</section>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="Daily visitors" subtitle="Unique visitors per day">
        <x-admin.columns :series="$visitorsDaily" label="Visitors"/>
    </x-admin.panel>
    <x-admin.panel title="Daily sign-ups" subtitle="New member accounts per day">
        <x-admin.columns :series="$signupsDaily" label="Sign-ups"/>
    </x-admin.panel>
</div>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-admin.panel title="Newest members" :pad="false">
        <x-slot:actions>@if(auth()->user()->canAdmin('users'))<a href="{{ route('admin.users') }}" class="text-xs font-medium text-muted hover:text-ink">All users →</a>@endif</x-slot:actions>
        <ul class="divide-y divide-line">
            @foreach($recentUsers as $u)
                <li class="flex items-center gap-3 px-5 py-3">
                    <x-avatar :user="$u" size="size-9" class="text-xs"/>
                    <div class="min-w-0 flex-1">
                        @if(auth()->user()->canAdmin('users'))<a href="{{ route('admin.users.show', $u) }}" class="block truncate text-sm font-medium hover:underline">{{ $u->name }}</a>@else<p class="truncate text-sm font-medium">{{ $u->name }}</p>@endif
                        <p class="truncate text-xs text-muted">{{ collect([$u->is_freelancer ? 'Freelancer' : null, $u->is_client ? 'Client' : null])->filter()->implode(' · ') ?: 'Member' }} · joined {{ $u->created_at->diffForHumans() }}</p>
                    </div>
                    <x-status :status="$u->status"/>
                </li>
            @endforeach
        </ul>
    </x-admin.panel>
    <x-admin.panel title="Recent admin activity" :pad="false">
        <x-slot:actions>@if(auth()->user()->canAdmin('audit'))<a href="{{ route('admin.audit') }}" class="text-xs font-medium text-muted hover:text-ink">Audit log →</a>@endif</x-slot:actions>
        <ul class="divide-y divide-line">
            @forelse($activity as $log)
                <li class="px-5 py-3 text-sm">
                    <p><span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span> <span class="text-ink-soft">{{ \App\Support\Labels::action($log->action) }}</span></p>
                    <p class="truncate text-xs text-muted">{{ $log->created_at->diffForHumans() }}@if($log->reason) · “{{ $log->reason }}”@endif</p>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-muted">No admin actions yet.</li>
            @endforelse
        </ul>
    </x-admin.panel>
</div>
</x-layouts.admin>
