@props(['counts' => []])
{{--
    The one navigation bar for every signed-in page. Each destination appears exactly once:
      • logo            → Explore feed
      • section links   → workspace-specific (freelancer: find work / portfolio …; client: jobs / talent …)
      • primary button  → Share Work (freelancer) or Post a Job (client)
      • icons           → Messages, Notifications
      • avatar menu     → Profile, (Saved jobs), Preferences (Settings, Billing, Help), Admin, Switch workspace, Sign out
--}}
@if(auth()->user()->inAdminMode())
@php
    $me = auth()->user();
    $adminLinks = array_values(array_filter([
        ['Console', 'admin.overview', ['admin.*'], true],
        ['Explore', 'home', ['home', 'portfolio.show'], true],
        ['Talent', 'freelancers.index', ['freelancers.*'], true],
        ['Jobs', 'jobs.index', ['jobs.index', 'jobs.show'], true],
    ], fn ($l) => $l[3]));
    $openQueues = collect(\App\Support\AdminMenu::counts())->except('health')->sum();
@endphp
{{-- Staff top bar: browse the site as an operator — no member workspaces, posting, applying or billing. --}}
<header {{ $attributes->merge(['class' => 'sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur']) }} x-data="{ menu: false }" @keydown.escape="menu = false">
    <div class="flex h-16 items-center gap-2 px-4 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:gap-4">
        <div class="flex min-w-0 items-center gap-2 justify-self-start">
        <button type="button" @click="{{ request()->routeIs('admin.*') ? "\$dispatch('open-console')" : 'menu = !menu' }}" :aria-expanded="menu" aria-controls="admin-nav-mobile" class="-ml-1 grid size-10 place-items-center rounded-xl hover:bg-zinc-100 lg:hidden" aria-label="Menu">
            <x-icon name="menu" x-show="!menu"/><x-icon name="x" x-show="menu" x-cloak/>
        </button>
        <x-logo class="shrink-0"/>
        <span class="ml-1 hidden rounded-full bg-ink px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-white sm:inline">Admin</span>
        </div>
        <nav class="hidden h-16 justify-self-center items-stretch gap-1 lg:flex" aria-label="Admin">
            @foreach($adminLinks as [$label, $route, $patterns])
                @php $active = request()->routeIs(...$patterns); @endphp
                <a href="{{ route($route) }}" @if($active) aria-current="page" @endif class="flex items-center whitespace-nowrap border-b-2 px-2.5 text-[15px] xl:px-3 font-medium transition {{ $active ? 'border-ink text-ink' : 'border-transparent text-ink-soft hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-1.5 justify-self-end sm:gap-2">
            @unless(request()->routeIs('admin.*'))
            <a href="{{ route('admin.overview') }}" class="btn btn-dark btn-pill hidden h-10 sm:inline-flex"><x-icon name="shield" class="size-4"/> Open console @if($openQueues)<span class="rounded-full bg-white/20 px-1.5 text-[11px] tabular-nums">{{ $openQueues }}</span>@endif</a>
            @endunless
            <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                <button @click="open = !open" :aria-expanded="open" class="flex items-center rounded-full p-0.5 ring-2 ring-transparent transition hover:ring-ink/15" aria-label="Account menu">
                    <x-avatar :user="$me" size="size-9" class="text-xs"/>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right class="absolute right-0 top-full z-50 mt-2 w-64 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                    <div class="flex items-center gap-3 px-3 py-3">
                        <x-avatar :user="$me" size="size-10" class="text-xs"/>
                        <span class="min-w-0"><span class="block truncate font-semibold">{{ $me->name }}</span><span class="block truncate text-xs text-muted">{{ $me->adminRoleLabel() }}</span></span>
                    </div>
                    <div class="my-1 h-px bg-line"></div>
                    <a href="{{ route('admin.overview') }}" class="{{ 'flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50' }}"><x-icon name="shield" class="size-4 text-muted"/> Admin console</a>
                    <a href="{{ route('settings', ['tab' => 'account']) }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="settings" class="size-4 text-muted"/> Account & password</a>
                    @if($me->hasMemberWorkspace())
                        <form method="POST" action="{{ route('admin.mode') }}">@csrf<input type="hidden" name="mode" value="member"><button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="swap" class="size-4 text-muted"/> Use the site as a member</button></form>
                    @endif
                    <div class="my-1 h-px bg-line"></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4"/> Sign out</button></form>
                </div>
            </div>
        </div>
    </div>
    <nav id="admin-nav-mobile" x-show="menu" x-cloak x-transition.origin.top @click.outside="menu = false" class="border-t border-line bg-white px-3 py-2 lg:hidden" aria-label="Admin">
        @foreach($adminLinks as [$label, $route, $patterns])
            <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2.5 text-[15px] {{ request()->routeIs(...$patterns) ? 'bg-zinc-100 font-semibold' : 'hover:bg-zinc-50' }}">{{ $label }}</a>
        @endforeach
    </nav>
</header>
@else
@php
    $user = auth()->user();
    $ws = $user->workspace();
    $ownProfile = request()->routeIs('freelancers.show') && optional(request()->route('profile'))->user_id === $user->id;
    $links = $ws === 'client' ? [
        ['Dashboard', 'dashboard', ['dashboard']],
        ['My Jobs', 'client.jobs.index', ['client.jobs.*', 'applications.show']],
        ['Find Talent', 'freelancers.index', ['freelancers.index', 'freelancers.show']],
        ['Saved Talent', 'client.saved', ['client.saved']],
        ['Projects', 'projects.index', ['projects.*']],
    ] : [
        ['Dashboard', 'dashboard', ['dashboard']],
        ['Find Work', 'jobs.index', ['jobs.index', 'jobs.show', 'applications.create']],
        ['Portfolio', 'portfolio.index', ['portfolio.index', 'portfolio.create', 'portfolio.edit']],
        ['Proposals', 'applications.mine', ['applications.mine', 'applications.show']],
        ['Invitations', 'invitations.index', ['invitations.*']],
        ['Projects', 'projects.index', ['projects.*']],
    ];
    $isActive = fn ($patterns) => request()->routeIs(...$patterns) && ! $ownProfile;
    $msg = (int) ($counts['messages'] ?? 0);
    $ntf = (int) ($counts['notifications'] ?? 0);
    $item = 'flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-zinc-50';
@endphp
<header {{ $attributes->merge(['class' => 'sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur']) }} x-data="{ menu: false }" @keydown.escape="menu = false">
    <div class="flex h-16 items-center gap-2 px-4 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:gap-4">
        <div class="flex min-w-0 items-center gap-2 justify-self-start">
        <button type="button" @click="menu = !menu" :aria-expanded="menu" aria-controls="app-nav-mobile" class="-ml-1 grid size-10 place-items-center rounded-xl hover:bg-zinc-100 lg:hidden" aria-label="Menu">
            <x-icon name="menu" x-show="!menu"/><x-icon name="x" x-show="menu" x-cloak/>
        </button>
        <x-logo class="shrink-0"/>
        </div>

        <nav class="hidden h-16 justify-self-center items-stretch gap-1 lg:flex" aria-label="{{ ucfirst($ws) }} workspace">
            @foreach($links as [$label, $route, $patterns])
                @php $active = $isActive($patterns); @endphp
                <a href="{{ route($route) }}" @if($active) aria-current="page" @endif
                   class="flex items-center whitespace-nowrap border-b-2 px-2.5 text-[15px] xl:px-3 font-medium transition {{ $active ? 'border-ink text-ink' : 'border-transparent text-ink-soft hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-1.5 justify-self-end sm:gap-2">
            @if($ws === 'client')
                <a href="{{ route('client.jobs.create') }}" class="btn btn-light btn-pill hidden h-10 sm:inline-flex lg:max-xl:w-10 lg:max-xl:px-0"><x-icon name="plus" class="size-4"/><span class="lg:max-xl:sr-only">Post a Job</span></a>
            @elseif($user->freelancerProfile)
                <a href="{{ route('portfolio.create') }}" class="btn btn-light btn-pill hidden h-10 sm:inline-flex lg:max-xl:w-10 lg:max-xl:px-0"><x-icon name="plus" class="size-4"/><span class="lg:max-xl:sr-only">Share Work</span></a>
            @endif
            <a href="{{ route('messages.index') }}" title="Messages" class="relative grid size-10 place-items-center rounded-full hover:bg-zinc-100 {{ request()->routeIs('messages.*') ? 'bg-zinc-100' : '' }}" aria-label="Messages{{ $msg ? " ($msg unread)" : '' }}">
                <x-icon name="mail" class="size-[22px]"/>@if($msg)<span class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-leaf px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $msg }}</span>@endif
            </a>
            <a href="{{ route('notifications.index') }}" title="Notifications" class="relative grid size-10 place-items-center rounded-full hover:bg-zinc-100 {{ request()->routeIs('notifications.*') ? 'bg-zinc-100' : '' }}" aria-label="Notifications{{ $ntf ? " ($ntf unread)" : '' }}">
                <x-icon name="bell" class="size-[22px]"/>@if($ntf)<span class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-leaf px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $ntf }}</span>@endif
            </a>

            {{-- Avatar menu --}}
            <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                <button @click="open = !open" :aria-expanded="open" class="flex items-center rounded-full p-0.5 ring-2 ring-transparent transition hover:ring-ink/15" aria-label="Account menu">
                    <x-avatar :user="$user" size="size-9" class="text-xs"/>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right class="absolute right-0 top-full z-50 mt-2 w-72 rounded-2xl border border-line bg-white p-1.5 shadow-xl">
                    <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-zinc-50">
                        <x-avatar :user="$user" size="size-12" class="text-sm"/>
                        <span class="min-w-0"><span class="block truncate font-semibold">{{ $user->name }}</span><span class="block truncate text-xs text-muted">View your profile</span></span>
                    </a>
                    @if($ws === 'freelancer')
                        <a href="{{ route('jobs.saved') }}" class="{{ $item }}"><x-icon name="bookmark" class="size-4 text-muted"/> Saved jobs</a>
                    @endif
                    <div class="my-1 h-px bg-line"></div>
                    <p class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wider text-faint">Preferences</p>
                    <a href="{{ route('settings') }}" class="{{ $item }}"><x-icon name="settings" class="size-4 text-muted"/> Settings</a>
                    <a href="{{ route('billing.index') }}" class="{{ $item }}"><x-icon name="dollar" class="size-4 text-muted"/> Billing</a>
                    <a href="{{ route('how') }}" class="{{ $item }}"><x-icon name="help" class="size-4 text-muted"/> Help</a>
                    @if($user->isAdmin())
                        <form method="POST" action="{{ route('admin.mode') }}">@csrf<input type="hidden" name="mode" value="admin"><button class="{{ $item }} w-full"><x-icon name="shield" class="size-4 text-muted"/> Back to admin mode</button></form>
                    @endif
                    <div class="my-1 h-px bg-line"></div>
                    <p class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wider text-faint">Workspace</p>
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
    </div>

    {{-- Phones/tablets: the section links (hidden in the bar at this size) --}}
    <nav id="app-nav-mobile" x-show="menu" x-cloak x-transition.origin.top @click.outside="menu = false" class="border-t border-line bg-white px-3 py-2 lg:hidden" aria-label="{{ ucfirst($ws) }} workspace">
        @foreach($links as [$label, $route, $patterns])
            <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2.5 text-[15px] {{ $isActive($patterns) ? 'bg-zinc-100 font-semibold' : 'hover:bg-zinc-50' }}">{{ $label }}</a>
        @endforeach
        @if($ws === 'client')
            <a href="{{ route('client.jobs.create') }}" class="btn btn-dark mt-2 w-full sm:hidden">Post a Job</a>
        @elseif($user->freelancerProfile)
            <a href="{{ route('portfolio.create') }}" class="btn btn-dark mt-2 w-full sm:hidden">Share Work</a>
        @endif
    </nav>
</header>
@endif
