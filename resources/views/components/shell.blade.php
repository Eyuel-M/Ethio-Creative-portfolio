@props(['title' => null])
{{-- Uses the signed-in app layout for members, the visitor layout otherwise. --}}
@auth
    <x-layouts.app :title="$title">{{ $slot }}</x-layouts.app>
@else
    {{-- Visitor listing pages (talent, jobs, profiles): content spans the full width; the top bar keeps its centred placement. --}}
    <x-layouts.public :title="$title"><div class="space-y-3 px-4 pb-16 pt-4 sm:space-y-4 sm:px-6">{{ $slot }}</div></x-layouts.public>
@endauth
