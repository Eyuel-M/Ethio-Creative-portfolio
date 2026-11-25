@props(['title' => null])
@php
    $user = auth()->user();
    $ws = $user->workspace();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => $title])</head>
<body class="min-h-screen bg-white pb-10">
@include('partials.site-banners')

<x-app-nav :counts="$navCounts ?? []"/>

@if(! $user->hasVerifiedEmail())
    <div class="bg-amber-50 px-6 py-2.5 text-center text-sm text-amber-800">Please verify your email address. <a class="font-semibold underline" href="{{ route('verification.notice') }}">Verify now</a></div>
@endif

<main>{{ $slot }}</main>

{{-- Slim fixed footer, Behance-style --}}
<footer class="fixed inset-x-0 bottom-0 z-30 hidden border-t border-line bg-white/95 backdrop-blur md:block">
    <div class="flex h-10 items-center gap-5 px-6 text-xs text-ink-soft">
        <a href="{{ route('how') }}" class="font-medium hover:text-ink">How it works</a>
        <span class="h-4 w-px bg-line"></span>
        <a href="{{ route('insights.index') }}" class="hover:text-ink">Insights</a>
        <a href="{{ route('pricing') }}" class="hover:text-ink">Pricing</a>
        <a href="{{ route('how') }}#trust" class="hover:text-ink">Trust &amp; safety</a>
        <a href="{{ route('how') }}#faq" class="hover:text-ink">Help</a>
        <x-logo class="scale-75"/>
    </div>
</footer>
<x-flash/>
</body>
</html>
