@if(session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition class="fixed bottom-5 right-5 z-50 flex max-w-sm items-start gap-3 rounded-2xl bg-ink px-4 py-3 text-sm text-white shadow-2xl" role="status">
        <x-icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-leaf"/>
        <p class="leading-snug">{{ session('status') }}</p>
        <button type="button" @click="show = false" class="ml-1 text-white/60 hover:text-white" aria-label="Dismiss"><x-icon name="x" class="size-4"/></button>
    </div>
@endif
@if($errors->any())
    <div x-data="{ show: true }" x-show="show" class="fixed bottom-5 right-5 z-50 max-w-sm rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm shadow-2xl" role="alert">
        <div class="flex items-start gap-3">
            <x-icon name="x-circle" class="mt-0.5 size-5 shrink-0 text-rose-500"/>
            <div><p class="font-semibold text-ink">Please check the form</p><p class="text-muted">{{ $errors->first() }}</p></div>
            <button type="button" @click="show = false" class="text-faint hover:text-ink" aria-label="Dismiss"><x-icon name="x" class="size-4"/></button>
        </div>
    </div>
@endif
