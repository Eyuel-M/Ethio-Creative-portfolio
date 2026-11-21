@php use App\Support\Money; $me = auth()->user(); @endphp
<x-layouts.admin :title="$user->name">
<x-admin.header :title="$user->name" :back="['Users', route('admin.users')]" :subtitle="$user->email.' · member #'.$user->id">
    <x-slot:actions>
        @if($fp?->is_public)<a href="{{ route('freelancers.show', $fp) }}" target="_blank" class="btn btn-sm btn-light"><x-icon name="arrow-up-right" class="size-4"/> Public profile</a>@endif
        @if($me->canAdmin('impersonate') && ! $user->isAdmin() && $user->status !== 'deleted')
            <x-admin.action :action="route('admin.users.impersonate', $user)" label="View as user" icon="eye" placeholder="Why do you need to see their view? (logged)">
                <p class="text-xs text-muted">Opens the site exactly as {{ \Illuminate\Support\Str::before($user->name, ' ') }} sees it. Everything is read-only and the session is logged.</p>
            </x-admin.action>
        @endif
    </x-slot:actions>
</x-admin.header>

<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-5">
        {{-- Identity --}}
        <section class="rounded-2xl border border-line bg-white p-5">
            <div class="flex flex-wrap items-center gap-4">
                <x-avatar :user="$user" size="size-16" class="text-lg"/>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status :status="$user->status"/>
                        @if($user->is_freelancer)<span class="chip">Freelancer · {{ ucfirst($fp?->plan ?? 'free') }}</span>@endif
                        @if($user->is_client)<span class="chip">Client{{ $cp?->is_verified ? ' · verified' : '' }}</span>@endif
                        @if($user->admin_role)<span class="chip border-ink bg-ink text-white">{{ $user->adminRoleLabel() }}</span>@endif
                        @if($user->email_verified_at)<span class="chip">Email verified</span>@else<span class="chip text-amber-700">Email not verified</span>@endif
                    </div>
                    <p class="mt-2 text-sm text-ink-soft">{{ $fp?->title ?? $cp?->displayName() ?? 'Member' }}{{ ($fp?->location ?? $cp?->location) ? ' · '.($fp?->location ?? $cp?->location) : '' }}</p>
                </div>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line sm:grid-cols-4">
                @foreach([
                    ['Joined', $user->created_at->format('M j, Y')],
                    ['Last active', $user->last_seen_at?->diffForHumans() ?? '—'],
                    ['Last sign-in', $user->last_login_at?->diffForHumans() ?? '—'],
                    ['Visits', number_format($analytics['sessions'])],
                    ['First came from', $analytics['first'] ? (\App\Support\Analytics::SOURCE_LABELS[$analytics['first']->source] ?? $analytics['first']->source).($analytics['first']->source_detail ? ' · '.$analytics['first']->source_detail : '') : '—'],
                    ['Pages viewed', number_format($analytics['pageviews'])],
                    ['Reports against', $reportsAgainst->count()],
                    ['Reports filed', $reportsBy],
                ] as [$k, $v])
                    <div class="bg-white px-4 py-3"><dt class="text-[11px] font-medium uppercase tracking-wider text-muted">{{ $k }}</dt><dd class="mt-0.5 truncate text-sm font-semibold">{{ $v }}</dd></div>
                @endforeach
            </dl>
        </section>

        {{-- Freelancer --}}
        @if($fp)
            <x-admin.panel title="Freelancer profile" :subtitle="$fp->category?->name.' · '.($fp->is_public ? 'public' : 'hidden').' · '.$fp->profile_views.' profile views · '.$fp->completion().'% complete'" :pad="false">
                <ul class="divide-y divide-line">
                    @forelse($fp->portfolioProjects as $p)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="size-full object-cover">@endif</span>
                            <div class="min-w-0 flex-1"><a href="{{ route('portfolio.show', $p) }}" target="_blank" class="block truncate text-sm font-medium hover:underline">{{ $p->title }}</a><p class="text-xs text-muted">{{ number_format($p->views) }} views · {{ $p->published_at?->format('M j, Y') ?? 'never published' }}</p></div>
                            <x-status :status="$p->status"/>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No portfolio projects.</li>
                    @endforelse
                </ul>
                @if($applications->isNotEmpty())
                    <div class="border-t border-line px-5 py-3 text-xs font-semibold uppercase tracking-wider text-muted">Recent applications</div>
                    <ul class="divide-y divide-line border-t border-line">
                        @foreach($applications as $a)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm"><span class="truncate">{{ $a->jobPost?->title }}</span><span class="flex shrink-0 items-center gap-2 text-xs text-muted">{{ Money::format($a->proposed_amount_minor, $a->currency) }} <x-status :status="$a->status"/></span></li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        @endif

        {{-- Client --}}
        @if($cp)
            <x-admin.panel title="Client" :subtitle="$cp->displayName().' · '.ucfirst($cp->type).($cp->industry ? ' · '.$cp->industry : '')" :pad="false">
                <ul class="divide-y divide-line">
                    @forelse($jobs as $j)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm"><a href="{{ route('jobs.show', $j) }}" target="_blank" class="truncate font-medium hover:underline">{{ $j->title }}</a><span class="flex shrink-0 items-center gap-2 text-xs text-muted">{{ $j->applications_count }} applicants <x-status :status="$j->status"/></span></li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No jobs posted.</li>
                    @endforelse
                </ul>
            </x-admin.panel>
        @endif

        {{-- Projects --}}
        @if($projects->isNotEmpty())
            <x-admin.panel title="Projects & agreements" :pad="false">
                <ul class="divide-y divide-line">
                    @foreach($projects as $p)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            @if($me->canAdmin('agreements'))<a href="{{ route('admin.agreements.show', $p) }}" class="truncate font-medium hover:underline">{{ $p->title }}</a>@else<span class="truncate font-medium">{{ $p->title }}</span>@endif
                            <span class="flex shrink-0 items-center gap-2 text-xs text-muted">{{ $p->client?->name }} ↔ {{ $p->freelancer?->name }} <x-status :status="$p->status"/></span>
                        </li>
                    @endforeach
                </ul>
            </x-admin.panel>
        @endif

        {{-- Money --}}
        @if($orders->isNotEmpty() || $entitlements->isNotEmpty())
            <x-admin.panel title="Orders & active purchases" :pad="false">
                <ul class="divide-y divide-line">
                    @foreach($entitlements as $e)<li class="flex items-center justify-between px-5 py-2.5 text-sm"><span>{{ $e->product?->name }}</span><span class="text-xs text-muted">active until {{ $e->ends_at->format('M j, Y') }}</span></li>@endforeach
                    @foreach($orders as $o)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm"><span class="truncate">{{ $o->product?->name }} · <span class="tabular-nums">{{ Money::format($o->amount_minor, $o->currency) }}</span></span><span class="flex shrink-0 items-center gap-2 text-xs text-muted">{{ $o->created_at->format('M j') }} <x-status :status="$o->status"/></span></li>
                    @endforeach
                </ul>
            </x-admin.panel>
        @endif

        {{-- Reports --}}
        @if($reportsAgainst->isNotEmpty())
            <x-admin.panel title="Reports about this member" :pad="false">
                <ul class="divide-y divide-line">
                    @foreach($reportsAgainst as $r)
                        <li class="px-5 py-3 text-sm"><p class="flex items-center justify-between gap-2"><span class="font-medium">{{ ucfirst($r->reason) }} · {{ $r->reportable_type }}</span><x-status :status="$r->status"/></p><p class="text-xs text-muted">by {{ $r->reporter?->name }} · {{ $r->created_at->diffForHumans() }}@if($r->details) · “{{ \Illuminate\Support\Str::limit($r->details, 120) }}”@endif</p></li>
                    @endforeach
                </ul>
            </x-admin.panel>
        @endif

        {{-- Activity --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <x-admin.panel title="Recent activity on the site" :pad="false">
                <ul class="divide-y divide-line">
                    @forelse($analytics['events'] as $e)
                        <li class="flex items-center justify-between gap-2 px-5 py-2.5 text-sm"><span>{{ \App\Support\Insights::eventLabel($e->name) }}</span><span class="text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($e->created_at)->diffForHumans() }}</span></li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No tracked activity.</li>
                    @endforelse
                </ul>
            </x-admin.panel>
            <x-admin.panel title="Audit trail" :pad="false">
                <ul class="divide-y divide-line">
                    @forelse($audit as $log)
                        <li class="px-5 py-2.5 text-sm"><p><span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span> {{ \App\Support\Labels::action($log->action) }}</p><p class="truncate text-xs text-muted">{{ $log->created_at->diffForHumans() }}@if($log->reason) · “{{ $log->reason }}”@endif</p></li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">Nothing logged yet.</li>
                    @endforelse
                </ul>
            </x-admin.panel>
        </div>
    </div>

    {{-- Actions --}}
    <aside class="space-y-5 xl:sticky xl:top-20">
        @if($me->canAdmin('users_manage') && $user->id !== $me->id && $user->status !== 'deleted')
            <x-admin.panel title="Account status">
                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="space-y-3">@csrf
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['active' => 'Active', 'restricted' => 'Restricted', 'suspended' => 'Suspended', 'banned' => 'Banned'] as $v => $l)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm has-[:checked]:border-ink has-[:checked]:bg-zinc-50"><input type="radio" name="status" value="{{ $v }}" @checked($user->status === $v) class="accent-ink"> {{ $l }}</label>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted">Suspended and banned members are signed out everywhere and can't sign in.</p>
                    <textarea name="reason" rows="2" required minlength="3" class="input h-auto py-2 text-sm" placeholder="Reason (logged)"></textarea>
                    <button class="btn btn-dark btn-sm w-full">Update status</button>
                </form>
            </x-admin.panel>
        @endif

        @if(($cp && $me->canAdmin('verification')) || ($fp && ($me->canAdmin('finance') || $me->canAdmin('users_manage') || $me->canAdmin('content'))))
            <x-admin.panel title="Quick actions">
                <div class="flex flex-wrap gap-2">
                    @if($cp && $me->canAdmin('verification'))
                        <x-admin.action :action="route('admin.users.verify', $user)" :fields="['verified' => $cp->is_verified ? 0 : 1]" :label="$cp->is_verified ? 'Remove verification' : 'Verify client'" icon="verified"/>
                    @endif
                    @if($fp && ($me->canAdmin('finance') || $me->canAdmin('users_manage')))
                        <x-admin.action :action="route('admin.users.plan', $user)" :fields="['plan' => $fp->plan === 'professional' ? 'free' : 'professional']" :label="$fp->plan === 'professional' ? 'Move to Free plan' : 'Grant Professional'" icon="sparkles"/>
                    @endif
                    @if($fp && $me->canAdmin('content'))
                        <x-admin.action :action="route('admin.users.feature', $user)" :fields="['days' => $fp->isFeatured() ? 0 : 14]" :label="$fp->isFeatured() ? 'Stop featuring' : 'Feature 14 days'" icon="star"/>
                    @endif
                </div>
                @if($verification)<p class="mt-3 text-xs text-muted">Latest verification request: <x-status :status="$verification->status"/> · {{ $verification->created_at->format('M j') }}</p>@endif
            </x-admin.panel>
        @endif

        <x-admin.panel title="Internal notes" subtitle="Only admins see these.">
            <form method="POST" action="{{ route('admin.users.note', $user) }}" class="space-y-2">@csrf
                <textarea name="body" rows="3" required class="input h-auto py-2 text-sm" placeholder="e.g. Called about a payment dispute; agreed to wait 3 days."></textarea>
                <button class="btn btn-light btn-sm w-full">Add note</button>
            </form>
            <ul class="mt-4 space-y-3">
                @foreach($user->adminNotes as $n)
                    <li class="rounded-xl bg-zinc-50 p-3 text-sm"><p class="whitespace-pre-line">{{ $n->body }}</p><p class="mt-1 text-[11px] text-muted">{{ $n->author?->name ?? 'Admin' }} · {{ $n->created_at->diffForHumans() }}</p></li>
                @endforeach
            </ul>
        </x-admin.panel>
    </aside>
</div>
</x-layouts.admin>
