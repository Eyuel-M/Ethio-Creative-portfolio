@php use App\Support\Money; @endphp
<x-layouts.admin title="Plans & fees">
<x-admin.header title="Plans & fees" subtitle="The price list shown on the Pricing page and at checkout. Changes apply to new purchases only and are logged."/>
<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    @foreach($products as $p)
        @php $s = $sales[$p->id] ?? null; @endphp
        <x-admin.panel :title="$p->name" :subtitle="ucfirst($p->audience).' · '.$p->code.' · '.($s ? $s->c.' sold · '.Money::format((int) $s->s, $p->currency) : 'no sales yet')">
            <x-slot:actions>@if($p->is_active)<span class="pill bg-emerald-50 text-emerald-700 ring-emerald-600/15">On sale</span>@else<span class="pill bg-zinc-100 text-zinc-600 ring-zinc-500/15">Hidden</span>@endif</x-slot:actions>
            <form method="POST" action="{{ route('admin.products.update', $p) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
                <div class="sm:col-span-2"><label class="label">Name</label><input name="name" value="{{ $p->name }}" class="input" required></div>
                <div class="sm:col-span-2"><label class="label">Tagline</label><input name="tagline" value="{{ $p->tagline }}" class="input"></div>
                <div><label class="label">Price ({{ $p->currency }})</label><input name="price" type="number" step="0.01" min="0" value="{{ Money::toMajor($p->price_minor) }}" class="input" required></div>
                <div><label class="label">Lasts (days)</label><input name="duration_days" type="number" min="1" value="{{ $p->duration_days }}" class="input" required></div>
                <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked($p->is_active)> Available to buy</label>
                <input name="reason" required minlength="3" class="input sm:col-span-2" placeholder="Reason for the change (logged)">
                <div class="sm:col-span-2"><button class="btn btn-dark btn-sm">Save changes</button></div>
            </form>
        </x-admin.panel>
    @endforeach
</div>
</x-layouts.admin>
