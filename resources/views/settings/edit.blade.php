@php $fp = $user->freelancerProfile; $cp = $user->clientProfile; $pd = $fp?->paymentDetails; @endphp
<x-layouts.app title="Settings">
<section class="card card-pad">
    <h1 class="font-display text-3xl font-bold">Settings</h1>
    <nav class="mt-5 flex flex-wrap gap-2">
        @if($fp)<a href="{{ route('settings', ['tab' => 'profile']) }}" class="btn btn-sm {{ $tab === 'profile' ? 'btn-dark' : 'btn-light' }}">Freelancer profile</a>
            <a href="{{ route('settings', ['tab' => 'payment']) }}" class="btn btn-sm {{ $tab === 'payment' ? 'btn-dark' : 'btn-light' }}"><x-icon name="lock" class="size-3.5"/> Payment details</a>@endif
        @if($cp)<a href="{{ route('settings', ['tab' => 'client']) }}" class="btn btn-sm {{ $tab === 'client' ? 'btn-dark' : 'btn-light' }}">Client profile</a>@endif
        <a href="{{ route('settings', ['tab' => 'account']) }}" class="btn btn-sm {{ $tab === 'account' ? 'btn-dark' : 'btn-light' }}">Account</a>
    </nav>
</section>

@if($tab === 'profile' && $fp)
<form method="POST" action="{{ route('settings.freelancer') }}" enctype="multipart/form-data" class="card card-pad">@csrf @method('PUT')
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="panel-title">Public profile</h2><p class="text-sm text-muted">What clients see in the directory and on your profile.</p></div><a href="{{ route('freelancers.show', $fp) }}" class="btn btn-sm btn-light"><x-icon name="eye" class="size-4"/> Preview</a></div>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2"><label class="label" for="title">Professional title</label><input id="title" name="title" value="{{ old('title', $fp->title) }}" class="input" required placeholder="Brand Identity Designer"><x-field-error name="title"/></div>
        <div class="md:col-span-2"><label class="label" for="bio">Bio</label><textarea id="bio" name="bio" rows="5" class="input">{{ old('bio', $fp->bio) }}</textarea></div>
        <div><label class="label" for="category_id">Primary category</label><select id="category_id" name="category_id" class="input" required>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $fp->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="label" for="location">Location</label><input id="location" name="location" value="{{ old('location', $fp->location) }}" class="input" placeholder="Addis Ababa, Ethiopia"></div>
        <div><label class="label" for="experience_level">Experience level</label><select id="experience_level" name="experience_level" class="input">@foreach(['entry' => 'Entry', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)<option value="{{ $v }}" @selected(old('experience_level', $fp->experience_level) === $v)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="label" for="years_experience">Years of experience</label><input id="years_experience" type="number" min="0" name="years_experience" value="{{ old('years_experience', $fp->years_experience) }}" class="input"></div>
        <div><label class="label" for="availability">Availability</label><select id="availability" name="availability" class="input">@foreach(['available' => 'Available', 'busy' => 'Busy', 'unavailable' => 'Unavailable'] as $v => $l)<option value="{{ $v }}" @selected(old('availability', $fp->availability) === $v)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="label" for="hourly_rate">Hourly rate</label><div class="flex"><input id="hourly_rate" type="number" step="0.01" name="hourly_rate" value="{{ old('hourly_rate', \App\Support\Money::toMajor($fp->hourly_rate_minor)) }}" class="input rounded-r-none"><select name="currency" class="input w-24 rounded-l-none border-l-0" aria-label="Currency">@foreach(config('marketplace.currencies') as $c)<option @selected($fp->currency === $c)>{{ $c }}</option>@endforeach</select></div></div>
        <div class="md:col-span-2"><label class="label" for="skills">Skills</label><input id="skills" name="skills" list="skills-dl" value="{{ old('skills', $fp->skills->pluck('name')->implode(', ')) }}" class="input" placeholder="Branding, Illustration, Packaging"><datalist id="skills-dl">@foreach($allSkills as $s)<option value="{{ $s }}">@endforeach</datalist></div>
        <div><label class="label" for="tools">Tools / software</label><input id="tools" name="tools" value="{{ old('tools', implode(', ', $fp->tools ?? [])) }}" class="input" placeholder="Illustrator, Figma"></div>
        <div><label class="label" for="languages">Languages</label><input id="languages" name="languages" value="{{ old('languages', implode(', ', $fp->languages ?? [])) }}" class="input" placeholder="Amharic, English"></div>
        <div><label class="label" for="website">Website</label><input id="website" type="url" name="website" value="{{ old('website', $fp->website) }}" class="input" placeholder="https://"><x-field-error name="website"/></div>
        <div><label class="label" for="cover">Profile cover image</label><input id="cover" type="file" name="cover" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2"></div>
    </div>
    <div class="mt-8 rounded-2xl border border-line p-5">
        <h3 class="flex items-center gap-2 font-semibold"><x-icon name="lock" class="size-4"/> Private contact details</h3>
        <p class="mt-1 text-sm text-muted">Never shown publicly. Shared only with a client after you both confirm a project agreement.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div><label class="label" for="contact_phone">Phone</label><input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $fp->contact_phone) }}" class="input" placeholder="+251 9…"></div>
            <div><label class="label" for="contact_email">Contact email</label><input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $fp->contact_email) }}" class="input" placeholder="{{ $user->email }}"></div>
        </div>
    </div>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" class="checkbox" @checked($fp->is_public)> Show my profile in the public directory</label>
        <button class="btn btn-dark">Save profile</button>
    </div>
</form>
@endif

@if($tab === 'payment' && $fp)
<div>
<form method="POST" action="{{ route('settings.payment') }}" class="card card-pad" x-data="{ show: false }">@csrf @method('PUT')
    <div class="flex items-center justify-between gap-3"><h2 class="panel-title">Direct payment details</h2><x-status :status="$pd->status ?? 'not_provided'"/></div>
    <p class="mt-1 text-sm text-muted">Provide a CBE account, a Telebirr number, or both. Clients pay you directly after a confirmed agreement.</p>
    <div class="mt-6 grid gap-5 md:grid-cols-2">
        <div class="rounded-2xl border border-line p-5">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="bank" class="size-5"/> Commercial Bank of Ethiopia</p>
            <p class="mt-1 text-sm text-muted">Saved: <span class="tabular-nums tracking-wide">{{ $maskedCbe ?? 'not provided' }}</span></p>
            <label class="label mt-4" for="cbe">{{ $maskedCbe ? 'Replace account number' : 'Account number' }}</label>
            <input id="cbe" name="cbe_account_number" inputmode="numeric" autocomplete="off" class="input tabular-nums tracking-wide" :type="show ? 'text' : 'password'" placeholder="1000xxxxxxxxx"><x-field-error name="cbe_account_number"/>
            @if($maskedCbe)<button name="clear" value="cbe" class="mt-2 text-xs text-rose-600 underline" onclick="return confirm('Remove saved CBE account?')">Remove</button>@endif
        </div>
        <div class="rounded-2xl border border-line p-5">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="phone" class="size-5"/> Telebirr</p>
            <p class="mt-1 text-sm text-muted">Saved: <span class="tabular-nums tracking-wide">{{ $maskedTelebirr ?? 'not provided' }}</span></p>
            <label class="label mt-4" for="telebirr">{{ $maskedTelebirr ? 'Replace Telebirr number' : 'Telebirr number' }}</label>
            <input id="telebirr" name="telebirr_number" inputmode="tel" autocomplete="off" class="input tabular-nums tracking-wide" :type="show ? 'text' : 'password'" placeholder="09xxxxxxxx"><x-field-error name="telebirr_number"/>
            @if($maskedTelebirr)<button name="clear" value="telebirr" class="mt-2 text-xs text-rose-600 underline" onclick="return confirm('Remove saved Telebirr number?')">Remove</button>@endif
        </div>
    </div>
    <fieldset class="mt-6"><legend class="label">Preferred method</legend>
        <div class="flex gap-2">@foreach(['cbe' => 'CBE', 'telebirr' => 'Telebirr'] as $v => $l)<label class="flex items-center gap-2 rounded-xl border border-line px-4 py-2.5 text-sm has-[:checked]:border-ink has-[:checked]:bg-zinc-50"><input type="radio" name="preferred_method" value="{{ $v }}" @checked($pd?->preferred_method === $v)> {{ $l }}</label>@endforeach</div>
    </fieldset>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" class="checkbox" x-model="show"> Show what I type</label>
        <button class="btn btn-dark">Save securely</button>
    </div>
</form>
</div>
@endif

@if($tab === 'client' && $cp)
<form method="POST" action="{{ route('settings.client') }}" enctype="multipart/form-data" class="card card-pad" x-data="{ type: '{{ old('type', $cp->type) }}' }">@csrf @method('PUT')
    <h2 class="panel-title">Client profile</h2>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2 grid w-80 grid-cols-2 overflow-hidden rounded-xl border border-line text-sm">@foreach(['individual' => 'Individual', 'company' => 'Company'] as $v => $l)<label class="cursor-pointer py-2.5 text-center has-[:checked]:bg-ink has-[:checked]:text-white {{ $loop->first ? 'border-r border-line' : '' }}"><input type="radio" name="type" value="{{ $v }}" x-model="type" class="sr-only">{{ $l }}</label>@endforeach</div>
        <div x-show="type === 'company'"><label class="label" for="company_name">Company name</label><input id="company_name" name="company_name" value="{{ old('company_name', $cp->company_name) }}" class="input"><x-field-error name="company_name"/></div>
        <div><label class="label" for="industry">Industry</label><input id="industry" name="industry" value="{{ old('industry', $cp->industry) }}" class="input"></div>
        <div><label class="label" for="cwebsite">Website</label><input id="cwebsite" type="url" name="website" value="{{ old('website', $cp->website) }}" class="input"></div>
        <div><label class="label" for="clocation">Location</label><input id="clocation" name="location" value="{{ old('location', $cp->location) }}" class="input"></div>
        <div><label class="label" for="cphone">Private phone</label><input id="cphone" name="contact_phone" value="{{ old('contact_phone', $cp->contact_phone) }}" class="input"></div>
        <div><label class="label" for="logo">Logo</label><input id="logo" type="file" name="logo" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2"></div>
    </div>
    <div class="mt-6 text-right"><button class="btn btn-dark">Save client profile</button></div>
</form>
@endif

@if($tab === 'account')
<form method="POST" action="{{ route('settings.account') }}" enctype="multipart/form-data" class="card card-pad">@csrf @method('PUT')
    <h2 class="panel-title">Account</h2>
    <div class="mt-6 flex items-center gap-4"><x-avatar :user="$user" size="size-16" class="text-lg"/><input type="file" name="avatar" accept="image/*" class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2"></div>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div><label class="label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required></div>
        <div><label class="label">Email</label><input value="{{ $user->email }}" class="input bg-zinc-50" disabled></div>
        <div><label class="label" for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="input" autocomplete="current-password"><x-field-error name="current_password"/></div>
        <div></div>
        <div><label class="label" for="password">New password</label><input id="password" type="password" name="password" class="input" autocomplete="new-password"><x-field-error name="password"/></div>
        <div><label class="label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password"></div>
    </div>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2">
            @unless($user->is_freelancer)<button form="enable-f" class="btn btn-light">Enable freelancer workspace</button>@endunless
            @unless($user->is_client)<button form="enable-c" class="btn btn-light">Enable client workspace</button>@endunless
        </div>
        <button class="btn btn-dark">Save account</button>
    </div>
</form>
<form id="enable-f" method="POST" action="{{ route('workspace.enable') }}">@csrf<input type="hidden" name="role" value="freelancer"></form>
<form id="enable-c" method="POST" action="{{ route('workspace.enable') }}">@csrf<input type="hidden" name="role" value="client"></form>
@endif
</x-layouts.app>
