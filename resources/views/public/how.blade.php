<x-layouts.public title="How it works">
<section class="hero-gradient border-b border-line">
    <div class="px-4 py-20 text-center sm:px-8">
        <span class="eyebrow">How it works</span>
        <h1 class="mt-5 font-display text-5xl font-bold tracking-tight sm:text-6xl">From portfolio to project,<br>without middlemen.</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-ink-soft">We help you get discovered, screen fairly and document the deal. Money for the work moves directly between client and freelancer.</p>
    </div>
</section>
<section class="px-4 py-20 sm:px-8">
    <div class="grid gap-12 md:grid-cols-2">
        @foreach(['For freelancers' => [
            ['Build a portfolio', 'Publish structured case studies with images, text and video. The free tier is genuinely useful.'],
            ['Apply with context', 'Answer the client\'s screening questions and attach projects you have already published.'],
            ['Discuss & negotiate', 'Chat once shortlisted or invited. Accepting an invitation only means you agree to talk.'],
            ['Confirm the agreement', 'Both of you confirm the exact same version. Then the workspace opens and your CBE / Telebirr details are shared with that client only.'],
        ], 'For clients' => [
            ['Publish a job', 'Pay the platform job-posting fee, add screening questions from templates or write your own.'],
            ['Compare applicants', 'See proposals, answers and portfolio picks side by side. Shortlist, reject or ask questions.'],
            ['Agree on terms', 'Scope, deliverables, price, deadline, revisions, milestones — versioned and confirmed by both.'],
            ['Pay directly', 'After confirmation you see the freelancer\'s payment details and pay them directly. We record terms, not payments.'],
        ]] as $who => $steps)
            <div>
                <h2 class="font-display text-2xl font-bold">{{ $who }}</h2>
                <ol class="mt-6 space-y-4">
                    @foreach($steps as $i => [$t, $d])
                        <li class="card flex gap-4 p-5"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">{{ $i + 1 }}</span><div><p class="font-semibold">{{ $t }}</p><p class="mt-1 text-sm text-muted">{{ $d }}</p></div></li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </div>

    <div id="trust" class="mt-20 rounded-3xl bg-ink p-8 text-white sm:p-12">
        <h2 class="font-display text-3xl font-bold">Ground rules we never break</h2>
        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @foreach([
                ['bank', 'We do not hold, escrow, split or take commission from project payments.'],
                ['lock', 'We never sell access to contact or payment details.'],
                ['file', 'A download is not acceptance. Receipt confirmation means receipt only.'],
                ['check-circle', 'Marking a milestone complete is a record — not proof of payment.'],
                ['shield', 'Payment details are encrypted at rest and every reveal is audit-logged.'],
                ['star', 'Reputation is earned from verified projects and can never be bought.'],
            ] as [$icon, $text])
                <div class="flex gap-3"><x-icon :name="$icon" class="size-5 shrink-0 text-leaf"/><p class="text-white/85">{{ $text }}</p></div>
            @endforeach
        </div>
    </div>

    <div id="faq" class="mt-20">
        <h2 class="font-display text-3xl font-bold">Questions</h2>
        <div class="mt-6 divide-y divide-line rounded-2xl border border-line bg-white" x-data="{ open: 0 }">
            @foreach([
                ['Is it free for freelancers?', 'Yes. A free profile, '.config('marketplace.portfolio_limits.free').' case studies and unlimited applications. Upgrades add portfolio capacity, analytics and labelled featured placement.'],
                ['How do I get paid?', 'Directly by the client, through the CBE account or Telebirr number you saved — after you both confirm the project agreement. The platform does not initiate or verify that payment.'],
                ['When does a client see my phone or payment details?', 'Only after you and that client each confirm the same agreement version. Not when you apply, chat, or accept an invitation.'],
                ['What if the terms change?', 'Any change creates a new version. Both parties must confirm it again.'],
                ['What does a client pay for?', 'A fee to publish each job for a set period, and optional featured placement. No subscriptions are required.'],
            ] as $i => [$q, $a])
                <div>
                    <button class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left font-medium" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}">{{ $q }}<x-icon name="chevron-down" class="size-5 shrink-0 transition" ::class="open === {{ $i }} && 'rotate-180'"/></button>
                    <div x-show="open === {{ $i }}" x-transition class="px-6 pb-5 text-sm leading-relaxed text-muted">{{ $a }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
</x-layouts.public>
