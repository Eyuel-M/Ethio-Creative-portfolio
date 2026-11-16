<x-auth-card title="Verify your email" subtitle="We sent a verification link to {{ auth()->user()->email }}.">
    <p class="text-sm text-muted">Click the link in the email to activate your account. Didn't get it?</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mt-5">@csrf<button class="btn btn-dark w-full">Resend verification email</button></form>
    @if($devLink)
        <div class="mt-5 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm">
            <p class="font-semibold text-amber-800">Local development</p>
            <p class="mt-1 text-amber-700">Mail is written to the log. <a href="{{ $devLink }}" class="font-semibold underline">Verify instantly</a></p>
        </div>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf<button class="text-sm text-muted underline">Sign out</button></form>
</x-auth-card>
