<x-layouts.app title="My profile">
<section class="card overflow-hidden">
    <div class="h-28 bg-gradient-to-br from-indigo-200 via-violet-100 to-sky-200 sm:h-36"></div>
    <div class="px-5 pb-6 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-end gap-5">
                <button type="button" @click="$dispatch('open-avatar-editor')" class="group/avatar relative z-10 -mt-14 shrink-0 rounded-full" aria-label="Change profile photo">
                    <x-avatar :user="$user" size="size-28" class="text-3xl ring-[6px] ring-white"/>
                    <span class="absolute inset-[6px] hidden place-items-center rounded-full bg-black/50 text-xs font-semibold text-white opacity-0 transition sm:grid sm:group-hover/avatar:opacity-100 sm:group-focus-visible/avatar:opacity-100">
                        <span class="flex flex-col items-center gap-1"><x-icon name="camera" class="size-5"/>Change photo</span>
                    </span>
                    <span class="absolute bottom-1 right-1 grid size-9 place-items-center rounded-full bg-ink text-white ring-4 ring-white sm:hidden"><x-icon name="camera" class="size-4"/></span>
                </button>
                <div class="pb-1 pt-4">
                    <h1 class="flex items-center gap-2 font-display text-3xl font-bold">{{ $user->name }} @if($client->is_verified)<x-icon name="verified" class="size-6 text-brand"/>@endif</h1>
                    <p class="text-ink-soft">{{ $client->type === 'company' && $client->company_name ? $client->company_name : 'Client' }}{{ $client->industry ? ' · '.$client->industry : '' }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('settings', ['tab' => 'client']) }}" class="btn btn-light"><x-icon name="pen" class="size-4"/> Edit details</a>
                <a href="{{ route('client.jobs.create') }}" class="btn btn-dark"><x-icon name="plus" class="size-4"/> Post a job</a>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-soft">
            @if($client->location)<span class="meta"><x-icon name="pin" class="size-4"/>{{ $client->location }}</span>@endif
            @if($client->website)<span class="meta"><x-icon name="link" class="size-4"/><a href="{{ $client->website }}" target="_blank" rel="noopener nofollow" class="underline">{{ parse_url($client->website, PHP_URL_HOST) }}</a></span>@endif
            <span class="meta"><x-icon name="calendar" class="size-4"/>Member since {{ $user->created_at->format('F Y') }}</span>
        </div>
    </div>
</section>

<div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach([['briefcase', 'Jobs posted', $stats['jobs']], ['megaphone', 'Live jobs', $stats['live']], ['folder', 'Hires', $stats['hires']], ['star', 'Rating from freelancers', $stats['reviews'] ? $stats['rating'].' ('.$stats['reviews'].')' : '—']] as [$icon, $label, $value])
        <div class="card p-5"><span class="grid size-10 place-items-center rounded-xl bg-brand-soft text-brand"><x-icon :name="$icon" class="size-5"/></span><p class="mt-3 font-display text-2xl font-bold">{{ $value }}</p><p class="text-sm text-muted">{{ $label }}</p></div>
    @endforeach
</div>

<div class="grid gap-3 sm:gap-4 lg:grid-cols-[1fr_380px]">
    <section class="card card-pad">
        <h2 class="panel-title">Reviews from freelancers</h2>
        @forelse($reviews as $r)
            <div class="mt-5 border-t border-line pt-5 first-of-type:border-0 first-of-type:pt-0">
                <div class="flex items-center gap-1">@for($i = 1; $i <= 5; $i++)<x-icon name="star" class="size-4 {{ $i <= $r->rating ? 'fill-amber-400 text-amber-400' : 'text-zinc-300' }}"/>@endfor</div>
                @if($r->body)<p class="mt-2 text-ink-soft">“{{ $r->body }}”</p>@endif
                <p class="mt-1 text-xs text-muted">{{ $r->reviewer->name }} · {{ $r->created_at->format('M Y') }}</p>
            </div>
        @empty
            <x-empty class="mt-5" icon="star" title="No reviews yet" text="Freelancers can review you after a completed project."/>
        @endforelse
    </section>
    <aside class="space-y-3 sm:space-y-4">
        <section class="card card-pad">
            <h2 class="font-semibold">Details</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted">Account</dt><dd class="font-medium">{{ ucfirst($client->type) }}</dd></div>
                @if($client->company_name)<div class="flex justify-between gap-4"><dt class="text-muted">Company</dt><dd class="font-medium">{{ $client->company_name }}</dd></div>@endif
                <div class="flex justify-between gap-4"><dt class="text-muted">Industry</dt><dd class="font-medium">{{ $client->industry ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Location</dt><dd class="font-medium">{{ $client->location ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Email</dt><dd class="truncate font-medium">{{ $user->email }}</dd></div>
            </dl>
        </section>
        @if($user->is_freelancer && $user->freelancerProfile)
            <a href="{{ route('freelancers.show', $user->freelancerProfile) }}" class="card card-pad flex items-center gap-3 hover:bg-zinc-50">
                <span class="grid size-10 place-items-center rounded-xl bg-zinc-100"><x-icon name="layers" class="size-5"/></span>
                <span class="flex-1"><span class="block font-semibold">Your freelancer profile</span><span class="text-sm text-muted">Portfolio, cover and public page</span></span>
                <x-icon name="chevron-right" class="size-5 text-muted"/>
            </a>
        @endif
    </aside>
</div>

<x-avatar-editor :user="$user"/>
</x-layouts.app>
