<x-auth-card title="Create your account" subtitle="One login — you can add the other workspace later.">
    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ role: '{{ old('role', $role === 'client' ? 'client' : 'freelancer') }}' }">@csrf
        <fieldset>
            <legend class="label">I want to</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['freelancer' => ['Find work', 'pen'], 'client' => ['Hire talent', 'briefcase']] as $val => [$label, $icon])
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3 text-sm font-medium transition" :class="role === '{{ $val }}' ? 'border-ink bg-zinc-50 ring-1 ring-ink' : 'border-line'">
                        <input type="radio" name="role" value="{{ $val }}" x-model="role" class="sr-only">
                        <x-icon :name="$icon" class="size-5"/> {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div><label class="label" for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" class="input" required autocomplete="name"><x-field-error name="name"/></div>
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autocomplete="email"><x-field-error name="email"/></div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" class="input" required autocomplete="new-password"></div>
            <div><label class="label" for="password_confirmation">Confirm</label><input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
        </div>
        <x-field-error name="password"/>
        <label class="flex items-start gap-2 text-sm text-ink-soft"><input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required> <span>I agree to the terms, privacy and acceptable-use policies.</span></label>
        <x-field-error name="terms"/>
        <button class="btn btn-dark h-12 w-full">Create account</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-ink underline underline-offset-4">Sign in</a></p>
</x-auth-card>
