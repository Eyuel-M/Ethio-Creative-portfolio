@props(['days'])
<div class="flex rounded-full border border-line bg-white p-1 text-[13px] font-medium">
    @foreach([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months'] as $d => $l)
        <a href="{{ request()->fullUrlWithQuery(['days' => $d]) }}" class="rounded-full px-3 py-1.5 {{ (int) $days === $d ? 'bg-ink text-white' : 'text-muted hover:text-ink' }}">{{ $l }}</a>
    @endforeach
</div>
