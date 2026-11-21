<x-layouts.admin title="Settings & switches">
<x-admin.header title="Settings & switches" subtitle="Turn platform features on and off without a deploy. Every change is logged with a reason."/>
<x-admin.panel>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-1">@csrf @method('PUT')
        @foreach($labels as $k => [$label, $help])
            <label class="flex cursor-pointer items-start justify-between gap-6 rounded-xl px-3 py-3 hover:bg-zinc-50" x-data="{ on: @js((bool) ($flags[$k] ?? false)) }">
                <span><span class="block text-sm font-medium">{{ $label }}</span><span class="block text-xs text-muted">{{ $help }}</span></span>
                <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="on ? 'bg-ink' : 'bg-zinc-300'">
                    <input type="checkbox" name="flags[{{ $k }}]" value="1" x-model="on" class="peer sr-only">
                    <span class="absolute left-0.5 size-5 rounded-full bg-white shadow transition" :class="on && 'translate-x-5'"></span>
                </span>
            </label>
        @endforeach
        <div class="flex flex-wrap items-center gap-3 border-t border-line pt-4"><input name="reason" required minlength="3" class="input max-w-md" placeholder="Reason for the change (logged)"><button class="btn btn-dark">Save switches</button></div>
    </form>
</x-admin.panel>
<x-admin.panel title="Prices and plans">
    <p class="text-sm text-muted">Fees and plan prices live under <a href="{{ route('admin.products') }}" class="font-medium text-ink underline">Money → Plans & fees</a>. Categories and skills are under <a href="{{ route('admin.categories') }}" class="font-medium text-ink underline">Content</a>.</p>
</x-admin.panel>
</x-layouts.admin>
