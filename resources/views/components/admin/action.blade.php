@props(['action', 'label', 'fields' => [], 'tone' => 'light', 'reason' => true, 'placeholder' => 'Reason (saved in the audit log)', 'confirm' => null, 'icon' => null, 'size' => 'sm'])
{{-- A button that opens a small form asking for a reason before the action runs. Every admin action explains itself. --}}
@php $btn = ['light' => 'btn-light', 'dark' => 'btn-dark', 'danger' => 'btn-danger'][$tone] ?? 'btn-light'; @endphp
<div x-data="{ open: false }" class="relative inline-block" @keydown.escape="open = false">
    <button type="button" @click="open = !open" class="btn {{ $size === 'sm' ? 'btn-sm' : '' }} {{ $btn }}">@if($icon)<x-icon :name="$icon" class="size-4"/>@endif {{ $label }}</button>
    <form x-show="open" x-cloak @click.outside="open = false" method="POST" action="{{ $action }}" x-transition.origin.top.right
          class="absolute right-0 top-full z-30 mt-2 w-80 space-y-3 rounded-2xl border border-line bg-white p-4 text-left shadow-xl">@csrf
        @foreach($fields as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <p class="text-sm font-semibold">{{ $label }}</p>
        {{ $slot }}
        @if($reason)<textarea name="reason" rows="3" required minlength="3" maxlength="300" class="input h-auto py-2 text-sm" placeholder="{{ $placeholder }}"></textarea>@endif
        @if($confirm)<p class="text-xs text-rose-600">{{ $confirm }}</p>@endif
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-sm btn-light" @click="open = false">Cancel</button><button class="btn btn-sm {{ $tone === 'danger' ? 'btn-danger' : 'btn-dark' }}">Confirm</button></div>
    </form>
</div>
