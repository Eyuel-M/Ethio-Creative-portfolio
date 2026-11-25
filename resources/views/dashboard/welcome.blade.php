@php
    $user = auth()->user();
    $first = explode(' ', $user->name)[0];
    $need = in_array($need ?? null, ['freelancer', 'client'], true) ? $need : null;
    $paths = [
        'freelancer' => [
            'Become a freelancer', 'pen', 'from-[#eef3ff] to-white',
            'Show your work and get hired by Ethiopian businesses.',
            ['Build a portfolio of your best projects', 'Apply to jobs with proposals', 'Get paid directly by clients — no commission'],
            'Start my portfolio',
        ],
        'client' => [
            'Hire creative talent', 'briefcase', 'from-[#f4efff] to-white',
            'Find designers, illustrators, photographers and animators.',
            ['Post a job and get proposals', 'Compare applicants side by side', 'Pay creatives directly, on clear agreements'],
            'Set up hiring',
        ],
    ];
@endphp
<x-layouts.app title="Welcome">
<div class="card card-pad">
    <div class="mx-auto max-w-4xl py-6 sm:py-10">
        <p class="text-sm font-medium text-muted">Welcome, {{ $first }}</p>
        <h1 class="mt-1 font-display text-3xl font-bold tracking-tight sm:text-4xl">How would you like to use {{ config('marketplace.brand') }}?</h1>
        <p class="mt-3 max-w-2xl text-ink-soft">
            @if($need === 'freelancer')
                To do that you need a freelancer profile. It takes a minute — then you can apply to jobs and share your work.
            @elseif($need === 'client')
                To do that you need a client account. It takes a minute — then you can post jobs and invite talent.
            @else
                You're browsing as a member: explore portfolios, jobs and talent, and save work you like. When you're ready, pick a path below — you can add the other one later.
            @endif
        </p>

        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($paths as $role => [$title, $icon, $tint, $lead, $points, $cta])
                <form method="POST" action="{{ route('workspace.enable') }}"
                      class="flex flex-col rounded-3xl border bg-gradient-to-b {{ $tint }} p-6 transition {{ $need === $role ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-zinc-300 hover:shadow-[0_20px_40px_-28px_rgba(12,12,16,.35)]' }}">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role }}">
                    <span class="grid size-11 place-items-center rounded-2xl bg-ink text-white"><x-icon :name="$icon" class="size-5"/></span>
                    <h2 class="mt-4 font-display text-xl font-bold">{{ $title }}</h2>
                    <p class="mt-1 text-sm text-ink-soft">{{ $lead }}</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach($points as $pt)<li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-leaf-dark"/> {{ $pt }}</li>@endforeach
                    </ul>
                    <button class="btn {{ $need === $role || ! $need ? 'btn-dark' : 'btn-light' }} mt-6 h-11 w-full">{{ $cta }} <x-icon name="arrow-right" class="size-4"/></button>
                </form>
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-line pt-6 text-sm">
            <span class="text-muted">Not ready yet?</span>
            <a href="{{ route('home') }}" class="font-medium hover:underline">Explore work</a>
            <a href="{{ route('freelancers.index') }}" class="font-medium hover:underline">Browse talent</a>
            <a href="{{ route('jobs.index') }}" class="font-medium hover:underline">See open jobs</a>
            <a href="{{ route('home', ['collection' => 'saved']) }}" class="font-medium hover:underline">Your collection</a>
        </div>
    </div>
</div>
</x-layouts.app>
