<x-auth-card title="Welcome back" subtitle="Sign in to your workspace.">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">@csrf
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="email"><x-field-error name="email"/></div>
        <div>
            <div class="flex justify-between"><label class="label" for="password">Password</label><a href="{{ route('password.request') }}" class="text-xs font-medium text-muted hover:text-ink">Forgot?</a></div>
            <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-soft"><input type="checkbox" name="remember" class="checkbox"> Keep me signed in</label>
        <button class="btn btn-dark h-12 w-full">Sign in</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">New here? <a href="{{ route('register') }}" class="font-semibold text-ink underline underline-offset-4">Create an account</a></p>
</x-auth-card>
