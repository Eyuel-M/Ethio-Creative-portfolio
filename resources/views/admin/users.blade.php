<x-layouts.app title="Users">
@include('admin._nav')
<section class="card overflow-x-auto">
    <form class="flex flex-wrap gap-2 p-5"><input name="q" value="{{ request('q') }}" class="input max-w-xs" placeholder="Name or email"><select name="role" class="input w-40"><option value="">All roles</option><option value="freelancer" @selected(request('role') === 'freelancer')>Freelancers</option><option value="client" @selected(request('role') === 'client')>Clients</option></select><select name="status" class="input w-40"><option value="">Any status</option>@foreach(['active', 'restricted', 'suspended', 'banned'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select><button class="btn btn-dark">Filter</button></form>
    <table class="w-full min-w-[800px] text-sm">
        <thead class="border-y border-line bg-zinc-50/70"><tr><th class="table-head px-5 py-3">User</th><th class="table-head px-4 py-3">Roles</th><th class="table-head px-4 py-3">Verified</th><th class="table-head px-4 py-3">Status</th><th class="table-head px-4 py-3">Joined</th><th class="table-head px-4 py-3">Action</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($users as $u)
                <tr>
                    <td class="px-5 py-3"><div class="flex items-center gap-3"><x-avatar :user="$u" size="size-8" class="text-[10px]"/><div><p class="font-medium">{{ $u->name }}</p><p class="text-xs text-muted">{{ $u->email }}</p></div></div></td>
                    <td class="px-4 py-3">{{ collect([$u->is_freelancer ? 'Freelancer' : null, $u->is_client ? 'Client' : null, $u->admin_role ? \App\Support\Labels::status($u->admin_role) : null])->filter()->implode(', ') }}</td>
                    <td class="px-4 py-3">{{ $u->email_verified_at ? 'Yes' : 'No' }}</td>
                    <td class="px-4 py-3"><x-status :status="$u->status"/></td>
                    <td class="px-4 py-3 text-muted">{{ $u->created_at->format('M j, Y') }}</td>
                    <td class="px-4 py-3">@if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.status', $u) }}" class="flex gap-1">@csrf<select name="status" class="input h-9 w-32 text-xs">@foreach(['active', 'restricted', 'suspended', 'banned'] as $s)<option @selected($u->status === $s)>{{ $s }}</option>@endforeach</select><input name="reason" required minlength="5" class="input h-9 w-40 text-xs" placeholder="Reason (logged)"><button class="btn btn-sm btn-light">Apply</button></form>
                    @endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $users->links() }}</div>
</section>
</x-layouts.app>
