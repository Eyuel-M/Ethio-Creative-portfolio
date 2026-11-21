@props(['title' => null])
@php $user = auth()->user(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => $title])</head>
<body class="min-h-screen bg-canvas">
@include('partials.site-banners')
<x-app-nav :counts="$navCounts ?? []"/>
@if(! $user->hasVerifiedEmail())
    <div class="bg-amber-50 px-6 py-2.5 text-center text-sm text-amber-800">Please verify your email address. <a class="font-semibold underline" href="{{ route('verification.notice') }}">Verify now</a></div>
@endif
<main class="space-y-3 px-3 py-4 sm:space-y-4 sm:px-6 sm:py-6">{{ $slot }}</main>
<x-flash/>
</body>
</html>
