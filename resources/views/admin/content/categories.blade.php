<x-layouts.admin title="Categories & skills">
<x-admin.header title="Categories & skills" subtitle="The taxonomy behind sign-up, profiles, job posts, filters and the feed. Changes show up across the site immediately."/>
<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="space-y-4">
        @foreach($categories as $c)
            <x-admin.panel :title="$c->name" :subtitle="($use['freelancers'][$c->id] ?? 0).' freelancers · '.($use['jobs'][$c->id] ?? 0).' jobs · '.($use['projects'][$c->id] ?? 0).' portfolio projects'">
                <x-slot:actions>
                    @if(! ($use['freelancers'][$c->id] ?? 0) && ! ($use['jobs'][$c->id] ?? 0) && ! ($use['projects'][$c->id] ?? 0))
                        <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost text-rose-600"><x-icon name="trash" class="size-4"/></button></form>
                    @endif
                </x-slot:actions>
                <details class="group">
                    <summary class="cursor-pointer list-none text-sm font-medium text-muted hover:text-ink"><span class="group-open:hidden">Edit details</span><span class="hidden group-open:inline">Close</span></summary>
                    <form method="POST" action="{{ route('admin.categories.update', $c) }}" class="mt-3 grid gap-3 sm:grid-cols-[1fr_140px_90px]">@csrf @method('PUT')
                        <input name="name" value="{{ $c->name }}" class="input" required>
                        <select name="icon" class="input">@foreach($icons as $i)<option value="{{ $i }}" @selected($c->icon === $i)>{{ $i }}</option>@endforeach</select>
                        <input name="sort_order" type="number" min="0" value="{{ $c->sort_order }}" class="input" title="Order">
                        <input name="description" value="{{ $c->description }}" class="input sm:col-span-2" placeholder="Short description">
                        <button class="btn btn-dark btn-sm">Save</button>
                    </form>
                </details>
                <div class="mt-4 flex flex-wrap gap-1.5">
                    @foreach($skills[$c->id] ?? [] as $s)
                        <form method="POST" action="{{ route('admin.skills.destroy', $s) }}" onsubmit="return confirm('Remove “{{ $s->name }}” everywhere?')">@csrf @method('DELETE')
                            <button class="group/s inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-ink-soft hover:bg-rose-50 hover:text-rose-700">{{ $s->name }} <x-icon name="x" class="size-3 opacity-40 group-hover/s:opacity-100"/></button>
                        </form>
                    @endforeach
                    <form method="POST" action="{{ route('admin.skills.store') }}" class="flex">@csrf<input type="hidden" name="category_id" value="{{ $c->id }}"><input name="name" required class="h-7 w-32 rounded-l-full border border-line px-3 text-xs" placeholder="Add skill"><button class="h-7 rounded-r-full border border-l-0 border-line bg-white px-2.5 text-xs font-medium hover:bg-zinc-50">+</button></form>
                </div>
            </x-admin.panel>
        @endforeach
    </div>
    <x-admin.panel title="New category">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-3">@csrf
            <input name="name" required class="input" placeholder="e.g. Fashion Design">
            <select name="icon" class="input">@foreach($icons as $i)<option value="{{ $i }}">{{ $i }}</option>@endforeach</select>
            <input name="description" class="input" placeholder="Short description (optional)">
            <button class="btn btn-dark w-full">Create category</button>
        </form>
    </x-admin.panel>
</div>
</x-layouts.admin>
