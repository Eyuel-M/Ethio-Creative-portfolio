<x-auth-card title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" class="input" required><x-field-error name="email"/></div>
        <div><label class="label" for="password">New password</label><input id="password" name="password" type="password" class="input" required><x-field-error name="password"/></div>
        <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" class="input" required></div>
        <button class="btn btn-dark h-12 w-full">Update password</button>
    </form>
</x-auth-card>
