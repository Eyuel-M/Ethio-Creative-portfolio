@props(['type', 'id', 'label' => 'Report'])
{{-- Lets a signed-in member flag content; lands in Admin → Inbox → Reports. --}}
@auth
<div x-data="{ open: false }" class="relative inline-block text-left" @keydown.escape="open = false">
    <button type="button" @click="open = !open" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink"><x-icon name="flag" class="size-4"/> {{ $label }}</button>
    <form x-show="open" x-cloak @click.outside="open = false" method="POST" action="{{ route('reports.store') }}" x-transition.origin.top.left
          class="absolute left-0 top-full z-40 mt-2 w-72 space-y-3 rounded-2xl border border-line bg-white p-4 shadow-xl">@csrf
        <input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="id" value="{{ $id }}">
        <p class="text-sm font-semibold">What's wrong?</p>
        <select name="reason" class="input" required>
            <option value="copyright">Not their work / copyright</option><option value="scam">Scam or fraud</option><option value="impersonation">Pretending to be someone else</option>
            <option value="inappropriate">Inappropriate</option><option value="spam">Spam</option><option value="other">Something else</option>
        </select>
        <textarea name="details" rows="2" maxlength="2000" class="input h-auto py-2 text-sm" placeholder="Details for our moderators (optional)"></textarea>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-sm btn-light" @click="open = false">Cancel</button><button class="btn btn-sm btn-dark">Send report</button></div>
    </form>
</div>
@endauth
