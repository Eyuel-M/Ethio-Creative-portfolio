@props(['counts' => [], 'mobileLinks' => false])
{{-- Top-right cluster used on every signed-in page: primary action, messages, notifications, account menu. --}}
@php
    $user = auth()->user();
    $ws = $user->workspace();
    $workspace = $ws === 'client' ? [
        ['Dashboard', 'dashboard', 'grid'], ['My Jobs', 'client.jobs.index', 'briefcase'], ['Post a job', 'client.jobs.create', 'plus'],
        ['Saved Talent', 'client.saved', 'bookmark'], ['Projects', 'projects.index', 'folder'],
    ] : [
        ['Dashboard', 'dashboard', 'grid'], ['My Portfolio', 'portfolio.index', 'layers'], ['My Proposals', 'applications.mine', 'file'],
        ['Invitations', 'invitations.index', 'sparkles'], ['Saved jobs', 'jobs.saved', 'bookmark'], ['Projects', 'projects.index', 'folder'],
    ];
    $msg = (int) ($counts['messages'] ?? 0);
    $ntf = (int) ($counts['notifications'] ?? 0);
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-1.5 sm:gap-2']) }}>
    @if($ws === 'client')
        <a href="{{ route('client.jobs.create') }}" class="btn btn-light btn-pill hidden h-10 sm:inline-flex">Post a Job</a>
    @elseif($user->freelancerProfile)
        <a href="{{ route('portfolio.create') }}" class="btn btn-light btn-pill hidden h-10 sm:inline-flex">Share Work</a>
    @endif
    <a href="{{ route('messages.index') }}" class="relative grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Messages{{ $msg ? " ($msg unread)" : '' }}">
        <x-icon name="mail" class="size-[22px]"/>@if($msg)<span class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-leaf ring-2 ring-white"></span>@endif
    </a>
    <a href="{{ route('notifications.index') }}" class="relative grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Notifications{{ $ntf ? " ($ntf unread)" : '' }}">
        <x-icon name="bell" class="size-[22px]"/>@if($ntf)<span class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-leaf ring-2 ring-white"></span>@endif
    </a>

    <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
        <button @click="open = !open" :aria-expanded="open" class="flex items-center gap-2 rounded-full p-0.5 ring-2 ring-transparent transition hover:ring-ink/15" aria-label="Account menu">
            <x-avatar :user="$user" size="size-9" class="text-xs"/>
        </button>
        <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right class="absolute right-0 top-full z-50 mt-2 w-72 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
            <div class="flex items-center gap-3 px-3 py-3">
                <x-avatar :user="$user" size="size-12" class="text-sm"/>
                <div class="min-w-0"><p class="truncate font-semibold">{{ $user->name }}</p><p class="truncate text-xs text-muted">{{ ucfirst($ws) }} workspace · {{ $user->email }}</p></div>
            </div>
            <div class="my-1 h-px bg-line"></div>
            @if($mobileLinks)
                <div class="md:hidden">
                    @foreach(array_merge([['Jobs', 'jobs.index', 'briefcase'], ['Talent', 'freelancers.index', 'users']], $workspace) as [$label, $route, $icon])
                        <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon :name="$icon" class="size-4 text-muted"/>{{ $label }}</a>
                    @endforeach
                    <div class="my-1 h-px bg-line"></div>
                </div>
            @endif
            @if($user->is_freelancer && $user->freelancerProfile)
                <a href="{{ route('freelancers.show', $user->freelancerProfile) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="user" class="size-4 text-muted"/> View public profile</a>
            @endif
            <a href="{{ route('settings') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="settings" class="size-4 text-muted"/> Settings</a>
            <a href="{{ route('billing.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="dollar" class="size-4 text-muted"/> Billing</a>
            @if($user->isAdmin())
                <a href="{{ route('admin.overview') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="shield" class="size-4 text-muted"/> Admin console</a>
            @endif
            <div class="my-1 h-px bg-line"></div>
            <p class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wider text-faint">Switch workspace</p>
            @foreach(['freelancer' => 'Freelancer', 'client' => 'Client'] as $key => $label)
                @php $has = $key === 'freelancer' ? $user->is_freelancer : $user->is_client; @endphp
                <form method="POST" action="{{ $has ? route('workspace.switch') : route('workspace.enable') }}">@csrf
                    <input type="hidden" name="{{ $has ? 'workspace' : 'role' }}" value="{{ $key }}">
                    <button class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><span>{{ $has ? $label : 'Enable '.$label }}</span>@if($ws === $key)<x-icon name="check" class="size-4 text-leaf-dark"/>@endif</button>
                </form>
            @endforeach
            <div class="my-1 h-px bg-line"></div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4"/> Sign out</button>
            </form>
        </div>
    </div>
</div>
