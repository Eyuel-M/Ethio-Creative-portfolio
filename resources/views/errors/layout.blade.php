{{-- Branded error page. Uses only the site stylesheet (the two allowed fonts) and makes no DB calls. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $code }} · {{ config('marketplace.brand', config('app.name')) }}</title>
@vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-canvas">
<main class="grid min-h-screen place-items-center px-4">
    <div class="card w-full max-w-md p-8 text-center">
        <a href="/" class="mx-auto mb-6 grid size-12 place-items-center rounded-xl bg-ink text-white" aria-label="Home">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 7h10M7 12h7M7 17h10"/><circle cx="18" cy="12" r="1.6" fill="#34c759" stroke="none"/></svg>
        </a>
        <p class="font-display text-6xl font-bold tracking-tight">{{ $code }}</p>
        <h1 class="mt-3 text-xl font-semibold">{{ $title }}</h1>
        @php $detail = isset($exception) && $code < 500 ? trim((string) $exception->getMessage()) : ''; @endphp
        <p class="mt-2 text-sm text-muted">{{ $detail !== '' ? $detail : $text }}</p>
        <div class="mt-7 flex justify-center gap-2">
            <a href="/" class="btn btn-dark">Go to home</a>
            <a href="javascript:history.back()" class="btn btn-light">Go back</a>
        </div>
    </div>
</main>
</body>
</html>
