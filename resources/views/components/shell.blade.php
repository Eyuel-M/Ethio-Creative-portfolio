@props(['title' => null])
{{-- Uses the sidebar app layout when signed in, the marketing layout for visitors. --}}
@auth
    <x-layouts.app :title="$title">{{ $slot }}</x-layouts.app>
@else
    <x-layouts.public :title="$title"><div class="mx-auto max-w-[1320px] px-4 pb-16 pt-6 sm:px-6">{{ $slot }}</div></x-layouts.public>
@endauth
