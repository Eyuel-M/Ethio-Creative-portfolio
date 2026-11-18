<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · ' : '' }}{{ config('marketplace.brand') }}</title>
<meta name="description" content="Portfolio-first marketplace for Ethiopian creative freelancers and the clients who hire them.">
@foreach(['node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2', 'node_modules/@fontsource-variable/inter-tight/files/inter-tight-latin-wght-normal.woff2'] as $font)
<link rel="preload" href="{{ Vite::asset($font) }}" as="font" type="font/woff2" crossorigin>
@endforeach
@vite(['resources/css/app.css', 'resources/js/app.js'])
