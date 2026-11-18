<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · ' : '' }}{{ config('marketplace.brand') }}</title>
<meta name="description" content="Portfolio-first marketplace for Ethiopian creative freelancers and the clients who hire them.">
<meta property="og:site_name" content="{{ config('marketplace.brand') }}">
@stack('meta')
@vite(['resources/css/app.css', 'resources/js/app.js'])
