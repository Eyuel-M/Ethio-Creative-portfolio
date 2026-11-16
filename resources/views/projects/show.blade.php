@php
    use App\Support\Money;
    $ag = $project->agreement;
    $me = auth()->user();
    $other = $isClient ? $project->freelancer : $project->client;
    $cancelRequester = $project->status === 'cancellation_requested' ? $project->statusHistory()->where('to_status', 'cancellation_requested')->first()?->actor_id : null;
@endphp
<x-layouts.app :title="$project->title">
<section class="card card-pad">
    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-icon name="chevron-left" class="size-4"/> Projects</a>
    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2"><x-status :status="$project->status"/><span class="text-xs text-muted">Agreement v{{ $ag->version }} · confirmed {{ $ag->confirmations->max('confirmed_at')?->format('M j, Y') }}</span></div>
            <h1 class="mt-2 font-display text-3xl font-bold">{{ $project->title }}</h1>
            <p class="mt-1 text-muted">with {{ $other->name }} · due {{ $ag->deadline->format('M j, Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($conversation)<a href="{{ route('messages.show', $conversation) }}" class="btn btn-light"><x-icon name="message" class="size-4"/> Chat</a>@endif
            {{-- Status actions --}}
            @php
                $actions = [];
                if (! $isClient) {
                    if (in_array($project->status, ['in_progress', 'revision_requested'])) $actions[] = ['submitted_for_review', 'Submit for review', 'btn-dark'];
                    if ($project->status === 'submitted_for_review') $actions[] = ['delivered', 'Mark delivered', 'btn-dark'];
                } else {
                    if (in_array($project->status, ['submitted_for_review', 'delivered'])) $actions[] = ['revision_requested', 'Request revision', 'btn-light'];
                    if ($project->status === 'delivered') $actions[] = ['completed', 'Mark completed', 'btn-dark'];
                }
            @endphp
            @foreach($actions as [$to, $label, $cls])
                <form method="POST" action="{{ route('projects.status', $project) }}">@csrf<input type="hidden" name="status" value="{{ $to }}"><button class="btn {{ $cls }}">{{ $label }}</button></form>
            @endforeach
        </div>
    </div>
    @if($project->status === 'cancellation_requested')
        <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl bg-rose-50 p-4 text-sm text-rose-800">
            <x-icon name="flag" class="size-5"/>
            <p class="flex-1">Cancellation requested{{ $cancelRequester === $me->id ? ' by you. Waiting for the other party to respond.' : ' by '.$other->name.'. Please respond.' }}</p>
            @if($cancelRequester !== $me->id)
                <form method="POST" action="{{ route('projects.status', $project) }}">@csrf<input type="hidden" name="status" value="in_progress"><button class="btn btn-sm btn-light">Decline &amp; continue</button></form>
                <form method="POST" action="{{ route('projects.status', $project) }}">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn btn-sm btn-danger">Accept cancellation</button></form>
            @endif
        </div>
    @endif
</section>

<div class="grid gap-3 sm:gap-4 xl:grid-cols-[1fr_360px]">
    <div class="space-y-3 sm:space-y-4">
        {{-- Milestones --}}
        <section class="card card-pad">
            <div class="flex items-center justify-between"><h2 class="panel-title">Milestones</h2><span class="text-sm text-muted">{{ $project->progress() }}% accepted</span></div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-gradient-to-r from-brand to-[#79a3ff]" style="width: {{ max(2, $project->progress()) }}%"></div></div>
            <ul class="mt-5 space-y-2">
                @forelse($project->milestones as $m)
                    <li class="flex flex-wrap items-center gap-3 rounded-xl border border-line p-4">
                        <span class="grid size-8 place-items-center rounded-full {{ $m->status === 'accepted' ? 'bg-leaf text-white' : 'bg-zinc-100 text-muted' }}"><x-icon :name="$m->status === 'accepted' ? 'check' : 'clock'" class="size-4"/></span>
                        <div class="min-w-0 flex-1"><p class="font-medium">{{ $m->title }}</p><p class="text-xs text-muted">{{ $m->due_date?->format('M j, Y') ?? 'No date' }}{{ $m->amount_minor ? ' · '.Money::format($m->amount_minor, $ag->currency).' (record only)' : '' }}</p></div>
                        <x-status :status="$m->status"/>
                        @php $mActions = $isClient ? ($m->status === 'submitted' ? [['accepted', 'Accept'], ['revision_requested', 'Request changes']] : []) : (match ($m->status) { 'not_started' => [['in_progress', 'Start']], 'in_progress', 'revision_requested' => [['submitted', 'Submit']], default => [] }); @endphp
                        @foreach($mActions as [$to, $label])
                            @if($m->canTransitionTo($to))<form method="POST" action="{{ route('projects.milestone', [$project, $m]) }}">@csrf<input type="hidden" name="status" value="{{ $to }}"><button class="btn btn-sm {{ $loop->first ? 'btn-dark' : 'btn-light' }}">{{ $label }}</button></form>@endif
                        @endforeach
                    </li>
                @empty
                    <li class="rounded-xl bg-zinc-50 p-4 text-sm text-muted">No milestones in this agreement — the project is tracked as a whole.</li>
                @endforelse
            </ul>
            <p class="mt-3 text-xs text-muted">Accepting a milestone is a project record. It does not move or confirm any payment.</p>
        </section>

        {{-- Files --}}
        <section class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="panel-title">Files</h2><p class="text-sm text-muted">Shared only between the two of you. Kept {{ config('marketplace.project_files.confirmed_retention_days') }} days after receipt, then deleted.</p></div>
                @if($project->status !== 'cancelled')
                <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data" x-data class="flex items-center gap-2">@csrf
                    <label class="btn btn-dark cursor-pointer"><x-icon name="upload" class="size-4"/> Upload<input type="file" name="file" class="sr-only" required @change="$el.form.requestSubmit()"></label>
                </form>
                @endif
            </div>
            <ul class="mt-5 divide-y divide-line rounded-2xl border border-line">
                @forelse($project->files as $f)
                    @php $downloads = $f->events->where('type', 'download_authorized')->count(); @endphp
                    <li class="flex flex-wrap items-center gap-3 p-4">
                        <span class="grid size-10 place-items-center rounded-xl bg-zinc-100"><x-icon name="file" class="size-5"/></span>
                        <div class="min-w-0 flex-1"><p class="truncate font-medium">{{ $f->original_name }}</p><p class="text-xs text-muted">{{ $f->sizeLabel() }} · {{ $f->uploader->name }} · {{ $f->created_at->diffForHumans() }} · {{ $downloads }} download{{ $downloads === 1 ? '' : 's' }} · SHA-256 {{ substr($f->sha256, 0, 10) }}…</p></div>
                        <x-status :status="$f->status"/>
                        <a href="{{ route('projects.files.download', $f) }}" class="btn btn-sm btn-light" aria-label="Download"><x-icon name="download" class="size-4"/></a>
                        @if($f->uploader_id !== $me->id && $f->status === 'available')
                            <form method="POST" action="{{ route('projects.files.receipt', $f) }}">@csrf<input type="hidden" name="action" value="confirm"><button class="btn btn-sm btn-dark" title="Confirms receipt only — not approval of the work">Confirm receipt</button></form>
                            <form method="POST" action="{{ route('projects.files.receipt', $f) }}">@csrf<input type="hidden" name="action" value="problem"><button class="btn btn-sm btn-ghost text-rose-600">Report problem</button></form>
                        @endif
                    </li>
                @empty
                    <li class="p-6 text-center text-sm text-muted">No files yet.</li>
                @endforelse
            </ul>
            <p class="mt-3 text-xs text-muted">A download is not acceptance. Receipt confirmation means the file arrived — not that it meets the brief.</p>
        </section>

        {{-- Agreement --}}
        <section class="card card-pad">
            <h2 class="panel-title">Confirmed agreement</h2>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div class="sm:col-span-2"><p class="text-muted">Scope</p><p class="mt-1 whitespace-pre-line">{{ $ag->scope }}</p></div>
                <div class="sm:col-span-2"><p class="text-muted">Deliverables</p><p class="mt-1 whitespace-pre-line">{{ $ag->deliverables }}</p></div>
                <div class="rounded-xl bg-zinc-50 p-3"><p class="text-muted">Agreed price (paid directly)</p><p class="font-semibold">{{ Money::format($ag->amount_minor, $ag->currency) }}</p></div>
                <div class="rounded-xl bg-zinc-50 p-3"><p class="text-muted">Revisions</p><p class="font-semibold">{{ $ag->revisions }}</p></div>
                @if($ag->cancellation_terms)<div class="sm:col-span-2"><p class="text-muted">Cancellation terms</p><p class="mt-1">{{ $ag->cancellation_terms }}</p></div>@endif
                <div class="sm:col-span-2 text-xs text-muted">@foreach($ag->confirmations as $c){{ $c->user->name }} confirmed {{ $c->confirmed_at->format('M j, Y H:i') }}@if(! $loop->last) · @endif @endforeach · Fingerprint {{ substr($ag->content_hash, 0, 12) }}</div>
            </div>
        </section>

        {{-- Timeline --}}
        <section class="card card-pad">
            <h2 class="panel-title">Timeline</h2>
            <ol class="mt-5 space-y-4 border-l border-line pl-5 text-sm">
                @foreach($project->events as $e)
                    <li class="relative"><span class="absolute -left-[25px] top-1 size-2.5 rounded-full bg-ink ring-4 ring-white"></span><p>{{ $e->description }}</p><p class="text-xs text-muted">{{ $e->created_at->format('M j, Y H:i') }}</p></li>
                @endforeach
            </ol>
        </section>
    </div>

    <aside class="space-y-3 sm:space-y-4">
        <section class="card card-pad">
            @if($private)
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="unlock" class="size-5 text-leaf-dark"/> {{ $isClient ? 'Freelancer contact & payment' : 'Client contact' }}</h2>
                <p class="mt-1 text-xs text-muted">Visible only to the two of you because you both confirmed agreement v{{ $ag->version }}.</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-muted">Name</dt><dd class="font-medium">{{ $private['party'] }}</dd></div>
                    <div><dt class="text-muted">Email</dt><dd class="font-medium">{{ $private['email'] }}</dd></div>
                    @if($private['phone'])<div><dt class="text-muted">Phone</dt><dd class="font-medium">{{ $private['phone'] }}</dd></div>@endif
                </dl>
                @if($private['show_payment'])
                    <div class="mt-5 space-y-2">
                        @if($private['cbe'])
                            <div class="rounded-xl border p-3 {{ $private['preferred'] === 'cbe' ? 'border-ink' : 'border-line' }}"><div class="flex items-center justify-between"><p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="bank" class="size-4"/> CBE account</p>@if($private['preferred'] === 'cbe')<span class="pill bg-ink text-white ring-ink">Preferred</span>@endif</div><p class="mt-1 font-mono text-lg tracking-wider" x-data><span x-ref="v">{{ $private['cbe'] }}</span> <button type="button" class="text-xs text-muted underline" @click="navigator.clipboard.writeText($refs.v.innerText)">copy</button></p></div>
                        @endif
                        @if($private['telebirr'])
                            <div class="rounded-xl border p-3 {{ $private['preferred'] === 'telebirr' ? 'border-ink' : 'border-line' }}"><div class="flex items-center justify-between"><p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="phone" class="size-4"/> Telebirr</p>@if($private['preferred'] === 'telebirr')<span class="pill bg-ink text-white ring-ink">Preferred</span>@endif</div><p class="mt-1 font-mono text-lg tracking-wider" x-data><span x-ref="v">{{ $private['telebirr'] }}</span> <button type="button" class="text-xs text-muted underline" @click="navigator.clipboard.writeText($refs.v.innerText)">copy</button></p></div>
                        @endif
                        @if(! $private['cbe'] && ! $private['telebirr'])
                            <p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">The freelancer hasn't added payment details yet. Ask them in chat to add CBE or Telebirr in their settings.</p>
                        @endif
                    </div>
                    <p class="mt-4 rounded-xl bg-zinc-50 p-3 text-xs text-muted">Pay the freelancer directly using the agreed method. The platform does not initiate, receive or verify this payment, and storing an account number does not prove ownership.</p>
                @else
                    <p class="mt-4 rounded-xl bg-zinc-50 p-3 text-xs text-muted">The client has your saved CBE/Telebirr details for this project and pays you directly. The platform does not confirm payments.</p>
                @endif
            @else
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="lock" class="size-5"/> Private details locked</h2>
                <p class="mt-2 text-sm text-muted">Both parties must confirm the same agreement version.</p>
            @endif
        </section>

        @if(! in_array($project->status, ['completed', 'cancelled', 'cancellation_requested']))
        <section class="card card-pad" x-data="{ o: false }">
            <h2 class="font-semibold">Need to stop?</h2>
            <p class="mt-1 text-sm text-muted">Either party can request cancellation with a reason. The other party gets to respond. Cancellation alone never counts as misconduct.</p>
            <button class="btn btn-danger mt-4 w-full" x-show="!o" @click="o = true">Request cancellation</button>
            <form method="POST" action="{{ route('projects.status', $project) }}" x-show="o" x-cloak class="mt-4 space-y-2">@csrf<input type="hidden" name="status" value="cancellation_requested"><textarea name="reason" class="input" rows="2" required minlength="5" placeholder="Reason"></textarea><button class="btn btn-danger w-full">Send request</button></form>
        </section>
        @endif

        @if(in_array($project->status, ['completed', 'cancelled']))
        <section class="card card-pad">
            <h2 class="font-semibold">Review {{ $other->name }}</h2>
            @if($myReview)
                <div class="mt-3 flex gap-1">@for($i = 1; $i <= 5; $i++)<x-icon name="star" class="size-5 {{ $i <= $myReview->rating ? 'fill-amber-400 text-amber-400' : 'text-zinc-300' }}"/>@endfor</div>
                <p class="mt-2 text-sm text-ink-soft">{{ $myReview->body }}</p>
            @else
                <form method="POST" action="{{ route('projects.review', $project) }}" class="mt-3 space-y-3" x-data="{ r: 5 }">@csrf
                    <div class="flex gap-1" role="radiogroup" aria-label="Rating">@for($i = 1; $i <= 5; $i++)<button type="button" @click="r = {{ $i }}" role="radio" :aria-checked="r === {{ $i }}" aria-label="{{ $i }} star"><x-icon name="star" class="size-7" ::class="r >= {{ $i }} ? 'fill-amber-400 text-amber-400' : 'text-zinc-300'"/></button>@endfor<input type="hidden" name="rating" :value="r"></div>
                    <textarea name="body" rows="3" class="input" placeholder="{{ $isClient ? 'Quality, communication, timeliness…' : 'Clarity, communication, respect for agreed terms…' }}"></textarea>
                    <button class="btn btn-dark w-full">Submit review</button>
                </form>
            @endif
        </section>
        @endif
    </aside>
</div>
</x-layouts.app>
