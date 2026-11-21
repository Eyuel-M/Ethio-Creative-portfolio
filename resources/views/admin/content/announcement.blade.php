<x-layouts.admin title="Announcement">
<x-admin.header title="Announcement banner" subtitle="A short message shown at the top of every page — maintenance windows, new features, holiday hours."/>
<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
    <x-admin.panel title="Message">
        <form method="POST" action="{{ route('admin.announcement.update') }}" class="space-y-4" x-data="{ text: @js($a['text']), tone: @js($a['tone']), on: @js((bool) $a['active']) }">@csrf @method('PUT')
            <label class="flex items-center gap-2 text-sm font-medium"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" x-model="on" class="checkbox"> Show the banner</label>
            <div><label class="label">Text</label><input name="text" x-model="text" maxlength="200" class="input" placeholder="e.g. Payments are paused Sunday 2–4am for maintenance."></div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="label">Link (optional)</label><input name="link" type="url" value="{{ $a['link'] }}" class="input" placeholder="https://"></div>
                <div><label class="label">Link text</label><input name="link_label" value="{{ $a['link_label'] }}" maxlength="40" class="input" placeholder="Learn more"></div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="label">Style</label><select name="tone" x-model="tone" class="input"><option value="info">Info (dark)</option><option value="success">Good news (green)</option><option value="warning">Warning (amber)</option></select></div>
                <div><label class="label">Who sees it</label><select name="audience" class="input">@foreach(['all' => 'Everyone', 'guests' => 'Visitors (signed out)', 'members' => 'Signed-in members', 'freelancers' => 'Freelancers', 'clients' => 'Clients'] as $v => $l)<option value="{{ $v }}" @selected($a['audience'] === $v)>{{ $l }}</option>@endforeach</select></div>
            </div>
            <div class="rounded-xl border border-dashed border-line p-3"><p class="mb-2 text-xs text-muted">Preview</p>
                <div class="rounded-lg px-4 py-2 text-center text-sm" :class="{ info: 'bg-ink text-white', success: 'bg-emerald-600 text-white', warning: 'bg-amber-400 text-ink' }[tone]" x-text="text || 'Your message'" :style="on ? '' : 'opacity:.4'"></div>
            </div>
            <button class="btn btn-dark">Save</button>
        </form>
    </x-admin.panel>
</div>
</x-layouts.admin>
