@props(['user', 'size' => 'size-10', 'ring' => false])
@php $url = $user?->avatarUrl(); $palette = ['bg-indigo-100 text-indigo-700','bg-emerald-100 text-emerald-700','bg-amber-100 text-amber-700','bg-rose-100 text-rose-700','bg-sky-100 text-sky-700','bg-violet-100 text-violet-700']; $tone = $palette[($user?->id ?? 0) % count($palette)]; @endphp
@if($url)
    <img src="{{ $url }}" alt="{{ $user->displayName() }}" {{ $attributes->merge(['class' => "$size shrink-0 rounded-full object-cover ".($ring ? 'ring-2 ring-white' : '')]) }} loading="lazy">
@else
    <span {{ $attributes->merge(['class' => "$size $tone shrink-0 rounded-full grid place-items-center font-semibold text-[0.8em] ".($ring ? 'ring-2 ring-white' : '')]) }} aria-label="{{ $user?->displayName() }}">{{ $user?->initials() }}</span>
@endif
