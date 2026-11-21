<!DOCTYPE html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Confirm it’s you'])</head>
<body class="grid min-h-screen place-items-center bg-zinc-100 p-4">
<main class="w-full max-w-sm rounded-3xl border border-line bg-white p-7 shadow-sm">
    <span class="grid size-11 place-items-center rounded-xl bg-ink text-white"><x-icon name="shield" class="size-5"/></span>
    <h1 class="mt-5 font-display text-2xl font-bold">Confirm it’s you</h1>
    <p class="mt-1 text-sm text-muted">We sent a 6-digit code to <span class="font-medium text-ink">{{ $email }}</span>. Admin access needs it every {{ config('admin.step_up_hours') }} hours.</p>
    @if(session('dev_code'))<p class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">Development only — your code is <span class="tabular-nums font-bold">{{ session('dev_code') }}</span></p>@endif
    <form method="POST" action="{{ route('admin.verify.confirm') }}" class="mt-5 space-y-3">@csrf
        <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus class="input h-12 text-center tabular-nums text-xl tracking-[0.5em]" placeholder="••••••">
        <x-field-error name="code"/>
        <button class="btn btn-dark h-11 w-full">Continue</button>
    </form>
    <div class="mt-4 flex justify-between text-sm"><a href="{{ route('admin.verify', ['resend' => 1]) }}" class="text-muted hover:text-ink">Send a new code</a><a href="{{ route('home') }}" class="text-muted hover:text-ink">Back to site</a></div>
</main>
</body>
</html>
