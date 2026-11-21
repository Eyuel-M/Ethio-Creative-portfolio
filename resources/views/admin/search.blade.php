<x-layouts.admin title="Search">
<x-admin.header title="Search" :subtitle="$q ? 'Results for “'.$q.'”' : 'Search people, jobs, portfolio projects, client projects and orders — by name, email, title or ID.'"/>
<form class="flex max-w-2xl gap-2"><input name="q" value="{{ $q }}" class="input" placeholder="Name, email, title, ID…" autofocus><button class="btn btn-dark">Search</button></form>
@if($q && ! $groups)
    <x-empty icon="search" title="Nothing found" text="Try an email, a full ID or part of a title."/>
@endif
<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    @foreach($groups as $type => $items)
        <x-admin.panel :title="$type" :subtitle="count($items).' result'.(count($items) === 1 ? '' : 's')" :pad="false">
            <ul class="divide-y divide-line">
                @foreach($items as $i)
                    <li><a href="{{ $i['url'] }}" class="block px-5 py-3 hover:bg-zinc-50"><span class="block text-sm font-medium">{{ $i['title'] }}</span><span class="block truncate text-xs text-muted">{{ $i['sub'] }}</span></a></li>
                @endforeach
            </ul>
        </x-admin.panel>
    @endforeach
</div>
</x-layouts.admin>
