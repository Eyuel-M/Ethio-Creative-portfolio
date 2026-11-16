@props(['status'])
<span {{ $attributes->merge(['class' => 'pill '.\App\Support\Labels::tone($status)]) }}>{{ \App\Support\Labels::status($status) }}</span>
