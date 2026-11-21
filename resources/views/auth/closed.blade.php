<x-layouts.public title="Sign-ups paused">
<div class="mx-auto max-w-lg px-4 py-24 text-center">
    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-zinc-100"><x-icon name="lock" class="size-6"/></span>
    <h1 class="mt-6 font-display text-3xl font-bold">Sign-ups are paused</h1>
    <p class="mt-3 text-ink-soft">We're not taking new accounts for a little while. Please check back soon — existing members can still sign in.</p>
    <a href="{{ route('login') }}" class="btn btn-dark btn-pill mt-6">Sign in</a>
</div>
</x-layouts.public>
