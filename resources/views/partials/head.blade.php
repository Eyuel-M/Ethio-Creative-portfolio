<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · ' : '' }}{{ config('marketplace.brand') }}</title>
@php $pushedMeta = $__env->yieldPushContent('meta'); @endphp
{{-- Pages can push their own description; the default is only used when they don't. --}}
@unless(str_contains($pushedMeta, 'name="description"'))<meta name="description" content="Portfolio-first marketplace for Ethiopian creative freelancers and the clients who hire them.">@endunless
<meta property="og:site_name" content="{{ config('marketplace.brand') }}">
{!! $pushedMeta !!}
@if(! empty($analyticsPageview))<meta name="ec-pv" content="{{ $analyticsPageview }}">@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
