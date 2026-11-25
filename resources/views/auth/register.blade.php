<x-auth-card title="Create your account" subtitle="One account. Browse now, and become a freelancer or a client whenever you're ready.">
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-4" x-data="{ role: '{{ old('role', $role ?? 'member') }}', name: @js(old('name', '')), photo: null,
          get initials() { return this.name.trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('') || '?' } }">@csrf
        <div class="flex items-center gap-4">
            <label class="relative grid size-16 shrink-0 cursor-pointer place-items-center overflow-hidden rounded-full bg-indigo-100 text-lg font-semibold text-indigo-700 ring-2 ring-white shadow" title="Add a profile photo">
                <img x-show="photo" :src="photo" alt="" class="size-full object-cover">
                <span x-show="!photo" x-text="initials"></span>
                <span class="absolute bottom-0 right-0 grid size-6 place-items-center rounded-full bg-ink text-white ring-2 ring-white"><x-icon name="camera" class="size-3.5"/></span>
                <input type="file" name="avatar" accept="image/*" class="sr-only" @change="photo = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
            </label>
            <div class="text-sm"><p class="font-medium">Profile photo <span class="font-normal text-muted">(optional)</span></p><p class="text-muted">Without one, we'll show your initials.</p><x-field-error name="avatar"/></div>
        </div>
        <fieldset>
            <legend class="label">What brings you here?</legend>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach(['freelancer' => ['Find work', 'pen', 'Show your portfolio'], 'client' => ['Hire talent', 'briefcase', 'Post jobs'], 'member' => ['Just exploring', 'eye', 'Decide later']] as $val => [$label, $icon, $sub])
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3 text-sm transition sm:flex-col sm:items-start sm:gap-1.5" :class="role === '{{ $val }}' ? 'border-ink bg-zinc-50 ring-1 ring-ink' : 'border-line hover:border-zinc-300'">
                        <input type="radio" name="role" value="{{ $val }}" x-model="role" class="sr-only">
                        <x-icon :name="$icon" class="size-5 shrink-0"/>
                        <span><span class="block font-medium">{{ $label }}</span><span class="block text-xs text-muted">{{ $sub }}</span></span>
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-muted" x-show="role === 'member'">You can browse portfolios, jobs and talent, and save work you like. Become a freelancer or a client any time.</p>
        </fieldset>
        <div><label class="label" for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" x-model="name" class="input" required autocomplete="name"><x-field-error name="name"/></div>
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
