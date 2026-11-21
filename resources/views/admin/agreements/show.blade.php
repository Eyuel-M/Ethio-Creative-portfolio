@php use App\Support\Money; $versions = $project->application?->agreements ?? collect(); @endphp
<x-layouts.admin :title="$project->title">
<x-admin.header :title="$project->title" :back="['Agreements & projects', route('admin.agreements')]" :subtitle="'Project '.$project->id.' · started '.$project->created_at->format('M j, Y')">
    <x-slot:actions><x-status :status="$project->status"/></x-slot:actions>
</x-admin.header>

<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="space-y-5">
        @foreach($versions as $v)
            <x-admin.panel :title="'Agreement v'.$v->version.($v->id === $project->agreement_version_id ? ' · the confirmed version' : '')" :subtitle="'Drafted by '.$v->author?->name.' · '.$v->created_at->format('M j, Y H:i').' · '.str_replace('_', ' ', $v->status)">
                <dl class="grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-muted">Agreed price (paid directly)</dt><dd class="font-semibold">{{ Money::format($v->amount_minor, $v->currency) }}</dd></div>
                    <div><dt class="text-xs text-muted">Deadline</dt><dd class="font-semibold">{{ $v->deadline->format('M j, Y') }}</dd></div>
                    <div><dt class="text-xs text-muted">Revisions</dt><dd class="font-semibold">{{ $v->revisions }}</dd></div>
                </dl>
                <div class="mt-4 space-y-3 text-sm">
                    <div><p class="text-xs text-muted">Scope</p><p class="whitespace-pre-line">{{ $v->scope }}</p></div>
                    <div><p class="text-xs text-muted">Deliverables</p><p class="whitespace-pre-line">{{ $v->deliverables }}</p></div>
                    @if($v->cancellation_terms)<div><p class="text-xs text-muted">Cancellation terms</p><p class="whitespace-pre-line">{{ $v->cancellation_terms }}</p></div>@endif
                    @if($v->milestones)<div><p class="text-xs text-muted">Milestones</p><ul class="mt-1 space-y-1">@foreach($v->milestones as $m)<li class="flex justify-between rounded-lg bg-zinc-50 px-3 py-1.5"><span>{{ $m['title'] }}</span><span class="text-muted">{{ $m['due_date'] ?? '' }} {{ ! empty($m['amount_minor']) ? '· '.Money::format($m['amount_minor'], $v->currency) : '' }}</span></li>@endforeach</ul></div>@endif
                </div>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach($v->confirmations as $c)
                        <div class="rounded-xl {{ hash_equals($v->computedHash(), $c->content_hash) ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }} px-3 py-2 text-xs">
                            <p class="font-semibold">{{ $c->user?->name }} confirmed</p><p>{{ $c->confirmed_at?->format('M j, Y H:i') }} · {{ hash_equals($v->computedHash(), $c->content_hash) ? 'terms match the confirmed text' : 'HASH MISMATCH — terms changed after confirming' }}</p>
                        </div>
                    @endforeach
                    @if($v->confirmations->isEmpty())<p class="text-xs text-muted">No confirmations on this version.</p>@endif
                </div>
            </x-admin.panel>
        @endforeach

        <x-admin.panel title="Project timeline" :pad="false">
            <ul class="divide-y divide-line">
                @forelse($project->events->sortByDesc('created_at') as $e)
                    <li class="flex items-start justify-between gap-3 px-5 py-2.5 text-sm"><span>{{ $e->description }}</span><span class="shrink-0 text-xs text-muted">{{ $e->created_at?->format('M j, H:i') }}</span></li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">No events.</li>
                @endforelse
            </ul>
        </x-admin.panel>
    </div>
    <aside class="space-y-5">
        <x-admin.panel title="Parties">
            @foreach([['Client', $project->client], ['Freelancer', $project->freelancer]] as [$role, $u])
                <a href="{{ route('admin.users.show', $u) }}" class="mb-3 flex items-center gap-3 last:mb-0"><x-avatar :user="$u" size="size-10" class="text-xs"/><span><span class="block text-sm font-medium hover:underline">{{ $u->name }}</span><span class="block text-xs text-muted">{{ $role }} · <x-status :status="$u->status"/></span></span></a>
            @endforeach
        </x-admin.panel>
        <x-admin.panel title="Milestones" :pad="false">
            <ul class="divide-y divide-line">@forelse($project->milestones as $m)<li class="flex justify-between gap-2 px-5 py-2.5 text-sm"><span class="truncate">{{ $m->title }}</span><x-status :status="$m->status"/></li>@empty<li class="px-5 py-4 text-sm text-muted">None.</li>@endforelse</ul>
        </x-admin.panel>
        <x-admin.panel title="Payment details revealed" subtitle="Each time the client opened the freelancer's CBE/Telebirr details." :pad="false">
            <ul class="divide-y divide-line">@forelse($disclosures as $d)<li class="px-5 py-2.5 text-sm">{{ $d->actor?->name ?? 'Unknown' }} <span class="block text-xs text-muted">{{ $d->created_at->format('M j, Y H:i') }}</span></li>@empty<li class="px-5 py-4 text-sm text-muted">Never revealed.</li>@endforelse</ul>
        </x-admin.panel>
        <x-admin.panel title="Files & reviews">
            <p class="text-sm">{{ $project->files->count() }} files shared</p>
            @foreach($project->reviews as $r)<p class="mt-2 text-sm">{{ str_repeat('★', $r->rating) }} <span class="text-muted">by {{ $r->reviewer?->name }}</span>@if($r->hidden_at) <span class="text-xs text-rose-600">(hidden)</span>@endif</p>@endforeach
        </x-admin.panel>
    </aside>
</div>
</x-layouts.admin>
