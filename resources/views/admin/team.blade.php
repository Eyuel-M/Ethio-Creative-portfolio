<x-layouts.admin title="Admin team">
<x-admin.header title="Admin team" subtitle="Who can use this console, and what each role can do. Every change is logged."/>
<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
    <x-admin.panel :pad="false" :title="$admins->count().' admins'">
        <ul class="divide-y divide-line">
            @foreach($admins as $a)
                <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                    <x-avatar :user="$a" size="size-10" class="text-xs"/>
                    <div class="min-w-0 flex-1"><a href="{{ route('admin.users.show', $a) }}" class="block truncate text-sm font-medium hover:underline">{{ $a->name }}@if($a->id === auth()->id()) <span class="text-muted">(you)</span>@endif</a><p class="truncate text-xs text-muted">{{ $a->email }} · last active {{ $a->last_seen_at?->diffForHumans() ?? 'never' }}</p></div>
                    <span class="chip border-ink bg-ink text-white">{{ $a->adminRoleLabel() }}</span>
                    @if($a->id !== auth()->id())
                        <x-admin.action :action="route('admin.team.update', $a)" label="Change">
                            <select name="role" class="input h-10 text-sm"><option value="">— Remove admin access —</option>@foreach($roles as $k => $l)<option value="{{ $k }}" @selected($a->admin_role === $k)>{{ $l }}</option>@endforeach</select>
                        </x-admin.action>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-admin.panel>
    <div class="space-y-5">
        <x-admin.panel title="Add an admin" subtitle="They need a member account first.">
            <form method="POST" action="{{ route('admin.team.store') }}" class="space-y-3">@csrf
                <input name="email" type="email" required class="input" placeholder="member@email.com" value="{{ old('email') }}">
                <select name="role" class="input">@foreach($roles as $k => $l)<option value="{{ $k }}" @selected(old('role', 'support') === $k)>{{ $l }}</option>@endforeach</select>
                <textarea name="reason" rows="2" required class="input h-auto py-2 text-sm" placeholder="Reason (logged)"></textarea>
                <button class="btn btn-dark w-full">Grant access</button>
            </form>
        </x-admin.panel>
        <x-admin.panel title="Roles">
            <dl class="space-y-3 text-sm">@foreach($roles as $k => $l)<div><dt class="font-medium">{{ $l }}</dt><dd class="text-xs text-muted">{{ $descriptions[$k] ?? '' }}</dd></div>@endforeach</dl>
        </x-admin.panel>
    </div>
</div>
</x-layouts.admin>
