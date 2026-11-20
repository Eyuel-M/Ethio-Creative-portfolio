@props(['title' => null])
{{-- Uses the sidebar app layout when signed in, the marketing layout for visitors. --}}
@auth
    <x-layouts.app :title="$title">{{ $slot }}</x-layouts.app>
@else
    <x-layouts.public :title="$title"><div class="space-y-3 px-4 pb-16 pt-4 sm:space-y-4 sm:px-8">{{ $slot }}</div></x-layouts.public>
@endauth
