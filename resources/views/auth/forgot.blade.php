<x-auth-card title="Reset password" subtitle="We'll email you a reset link.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">@csrf
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input" required><x-field-error name="email"/></div>
        <button class="btn btn-dark h-12 w-full">Send reset link</button>
    </form>
</x-auth-card>
