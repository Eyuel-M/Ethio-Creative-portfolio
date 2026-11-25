<x-layouts.admin title="Users">
<x-admin.header title="Users" subtitle="Every member account. Open one for the full picture and actions."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$f['status'] ?? ''" :items="['' => ['All', $statusCounts->sum()], 'active' => ['Active', $statusCounts['active'] ?? 0], 'restricted' => ['Restricted', $statusCounts['restricted'] ?? 0], 'suspended' => ['Suspended', $statusCounts['suspended'] ?? 0], 'banned' => ['Banned', $statusCounts['banned'] ?? 0], 'deleted' => ['Deleted', $statusCounts['deleted'] ?? 0]]"/>
    <form class="flex flex-wrap items-center gap-2 border-b border-line p-3 sm:px-5">
        @if(! empty($f['status']))<input type="hidden" name="status" value="{{ $f['status'] }}">@endif
        <div class="relative min-w-[220px] flex-1 sm:max-w-sm"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/><input name="q" value="{{ $f['q'] ?? '' }}" class="input h-10 pl-9" placeholder="Name, email or ID"></div>
        <div class="w-48"><select name="role" class="input h-10" onchange="this.form.submit()"><option value="">Any role</option>@foreach(['freelancer' => 'Freelancers', 'client' => 'Clients', 'member' => 'Undecided members', 'admin' => 'Admins'] as $v => $l)<option value="{{ $v }}" @selected(($f['role'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="w-40"><select name="plan" class="input h-10" onchange="this.form.submit()"><option value="">Any plan</option><option value="free" @selected(($f['plan'] ?? '') === 'free')>Free</option><option value="professional" @selected(($f['plan'] ?? '') === 'professional')>Professional</option></select></div>
        <div class="w-44"><select name="sort" class="input h-10" onchange="this.form.submit()">@foreach(['newest' => 'Newest first', 'oldest' => 'Oldest first', 'last_seen' => 'Recently active', 'name' => 'Name A–Z'] as $v => $l)<option value="{{ $v }}" @selected(($f['sort'] ?? 'newest') === $v)>{{ $l }}</option>@endforeach</select></div>
        <button class="btn btn-light h-10">Apply</button>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Member</th><th class="px-3 py-3">Roles</th><th class="px-3 py-3">Plan</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Joined</th><th class="px-3 py-3">Last active</th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse($users as $u)
                    <tr class="hover:bg-zinc-50">
                        <td class="px-5 py-3"><a href="{{ route('admin.users.show', $u) }}" class="flex items-center gap-3"><x-avatar :user="$u" size="size-9" class="text-xs"/><span class="min-w-0"><span class="block truncate font-medium hover:underline">{{ $u->name }}</span><span class="block truncate text-xs text-muted">{{ $u->email }}</span></span></a></td>
                        <td class="px-3 py-3"><span class="flex flex-wrap gap-1">@if(! $u->is_freelancer && ! $u->is_client && ! $u->admin_role)<span class="chip border-dashed text-muted" title="Signed up but hasn't chosen freelancer or client yet">Undecided</span>@endif @if($u->is_freelancer)<span class="chip">Freelancer</span>@endif @if($u->is_client)<span class="chip">Client{{ $u->clientProfile?->is_verified ? ' ✓' : '' }}</span>@endif @if($u->admin_role)<span class="chip border-ink bg-ink text-white">{{ $u->adminRoleLabel() }}</span>@endif</span></td>
                        <td class="px-3 py-3 text-ink-soft">{{ $u->freelancerProfile ? ucfirst($u->freelancerProfile->plan) : '—' }}</td>
                        <td class="px-3 py-3"><x-status :status="$u->status"/></td>
                        <td class="px-3 py-3 text-ink-soft">{{ $u->created_at->format('M j, Y') }}</td>
                        <td class="px-3 py-3 text-ink-soft">{{ $u->last_seen_at?->diffForHumans() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-muted">No members match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.panel>
{{ $users->links() }}
</x-layouts.admin>
