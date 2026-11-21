{{-- Site-wide bars: read-only "viewing as" notice for admins, and the announcement set in Admin → Content. --}}
@if(session('impersonator_id') && auth()->check())
    <div class="sticky top-0 z-[60] flex flex-wrap items-center justify-center gap-3 bg-amber-400 px-4 py-2 text-center text-sm font-medium text-ink">
        <x-icon name="eye" class="size-4"/> Viewing as {{ auth()->user()->name }} — read-only, nothing you do here is saved.
        <form method="POST" action="{{ route('impersonation.stop') }}">@csrf<button class="rounded-full bg-ink px-3 py-1 text-xs font-semibold text-white hover:bg-black">Back to admin</button></form>
    </div>
@endif
@php
    $ann = \App\Support\Settings::get('announcement');
    $u = auth()->user();
    $show = $ann['active'] && filled($ann['text']) && match ($ann['audience']) {
        'guests' => ! $u, 'members' => (bool) $u, 'freelancers' => $u?->is_freelancer, 'clients' => $u?->is_client, default => true,
    };
@endphp
@if($show)
    <div x-data="{ hide: false }" x-init="try { hide = sessionStorage.getItem('ann') === @js(md5($ann['text'])) } catch (e) {}" x-show="!hide"
         class="relative px-10 py-2.5 text-center text-sm {{ ['info' => 'bg-ink text-white', 'success' => 'bg-emerald-600 text-white', 'warning' => 'bg-amber-400 text-ink'][$ann['tone']] ?? 'bg-ink text-white' }}">
        {{ $ann['text'] }}@if($ann['link']) <a href="{{ $ann['link'] }}" class="ml-1 font-semibold underline underline-offset-2" data-track="cta_click" data-track-label="announcement">{{ $ann['link_label'] ?: 'Learn more' }}</a>@endif
        <button type="button" @click="hide = true; try { sessionStorage.setItem('ann', @js(md5($ann['text']))) } catch (e) {}" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-70 hover:opacity-100" aria-label="Dismiss"><x-icon name="x" class="size-4"/></button>
    </div>
@endif
