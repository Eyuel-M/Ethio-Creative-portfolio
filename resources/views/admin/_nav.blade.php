<section class="card card-pad">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="font-display text-3xl font-bold">Admin console</h1><p class="mt-1 text-sm text-muted">Role: {{ \App\Support\Labels::status(auth()->user()->admin_role) }} · every privileged action is audit-logged.</p></div></div>
    <nav class="mt-5 flex flex-wrap gap-2">
        @foreach([['Overview', 'admin.overview'], ['Users', 'admin.users'], ['Jobs', 'admin.jobs'], ['Orders', 'admin.orders'], ['Reports', 'admin.reports'], ['Audit log', 'admin.audit']] as [$l, $r])
            <a href="{{ route($r) }}" class="btn btn-sm {{ request()->routeIs($r) ? 'btn-dark' : 'btn-light' }}">{{ $l }}</a>
        @endforeach
    </nav>
</section>
