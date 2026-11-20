{{-- Renders one case-study block on the public project page. Settings come from PortfolioBlock::opt(). --}}
@php
    $bg = ['none' => '', 'light' => 'bg-zinc-50', 'dark' => 'bg-ink text-white', 'accent' => 'bg-violet-50'][$block->opt('bg') ?? 'none'] ?? '';
    $dark = $block->opt('bg') === 'dark';
    $pad = ['none' => '', 's' => 'py-6 sm:py-8', 'm' => 'py-12 sm:py-16', 'l' => 'py-16 sm:py-24'][$block->opt('pad') ?? 'm'] ?? '';
    $width = $block->opt('width') ?? 'wide';
    $inner = ['full' => 'w-full', 'wide' => 'mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-10', 'narrow' => 'mx-auto max-w-3xl px-5'][$width];
    $center = $block->opt('align') === 'center' ? 'text-center' : '';
    $soft = $dark ? 'text-white/75' : 'text-ink-soft';
    $capCls = 'mx-auto max-w-[1600px] px-4 py-3 text-sm sm:px-6 lg:px-10 '.($dark ? 'text-white/60' : 'text-muted');
@endphp
<section class="{{ $bg }} {{ $pad }}">
@switch($block->type)
    @case('heading')
        <div class="{{ $inner }} {{ $center }}">
            @if($block->opt('level') === 'h3')
                <h3 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ $block->body }}</h3>
            @else
                <h2 class="font-display text-3xl font-bold leading-tight tracking-tight sm:text-5xl">{{ $block->body }}</h2>
            @endif
        </div>
        @break

    @case('text')
        <div class="{{ $inner }} {{ $center }} whitespace-pre-line {{ $block->opt('size') === 'lg' ? 'text-xl leading-relaxed sm:text-2xl' : 'text-lg leading-relaxed' }} {{ $soft }} {{ $block->settings === null ? 'first-line:font-display first-line:text-2xl first-line:font-bold first-line:text-ink' : '' }}">{{ $block->body }}</div>
        @break

    @case('image')
        @if($block->mediaUrl())
            <figure class="{{ $width === 'full' ? '' : $inner }}">
                <img src="{{ $block->mediaUrl() }}" alt="{{ $block->alt ?? '' }}" class="block h-auto w-full {{ $width === 'full' ? '' : 'rounded-2xl' }}" loading="lazy">
                @if($block->caption)<figcaption class="{{ $width === 'full' ? $capCls : 'pt-3 text-sm '.($dark ? 'text-white/60' : 'text-muted') }}">{{ $block->caption }}</figcaption>@endif
            </figure>
        @endif
        @break

    @case('gallery')
        @php
            $cols = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-2 lg:grid-cols-3', '4' => 'grid-cols-2 lg:grid-cols-4'][$block->opt('cols')] ?? 'sm:grid-cols-2';
            $gap = ['none' => 'gap-0', 's' => 'gap-2', 'm' => 'gap-5'][$block->opt('gap')] ?? 'gap-2';
        @endphp
        <figure class="{{ $width === 'full' ? ($block->opt('gap') === 'none' ? '' : 'px-2') : $inner }}">
            <div class="grid {{ $cols }} {{ $gap }}">
                @foreach($block->galleryItems() as $g)
                    <img src="{{ $g['url'] }}" alt="{{ $g['alt'] }}" class="block aspect-[4/3] w-full object-cover {{ $block->opt('gap') === 'none' ? '' : 'rounded-xl' }}" loading="lazy">
                @endforeach
            </div>
            @if($block->caption)<figcaption class="{{ $capCls }}">{{ $block->caption }}</figcaption>@endif
        </figure>
        @break

    @case('image_text')
        <div class="{{ $inner }} grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
            @if($block->mediaUrl())
                <img src="{{ $block->mediaUrl() }}" alt="{{ $block->alt ?? '' }}" class="block h-auto w-full rounded-2xl {{ $block->opt('side') === 'right' ? 'lg:order-2' : '' }}" loading="lazy">
            @endif
            <div class="whitespace-pre-line text-lg leading-relaxed {{ $soft }} first-line:font-display first-line:text-2xl first-line:font-bold {{ $dark ? 'first-line:text-white' : 'first-line:text-ink' }}">{{ $block->body }}</div>
        </div>
        @break

    @case('quote')
        <figure class="{{ $inner }} {{ $center }}">
            <blockquote class="font-display text-2xl font-semibold leading-snug tracking-tight sm:text-4xl">“{{ $block->body }}”</blockquote>
            @if($block->caption)<figcaption class="mt-5 text-sm font-medium {{ $dark ? 'text-white/60' : 'text-muted' }}">— {{ $block->caption }}</figcaption>@endif
        </figure>
        @break

    @case('embed')
        @if($block->embedSrc())
            <figure class="{{ $width === 'full' ? '' : $inner }}">
                <div class="aspect-video w-full overflow-hidden bg-black {{ $width === 'full' ? '' : 'rounded-2xl' }}"><iframe src="{{ $block->embedSrc() }}" class="size-full" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" title="{{ $block->caption ?? 'Video' }}" sandbox="allow-scripts allow-same-origin allow-presentation"></iframe></div>
                @if($block->caption)<figcaption class="{{ $width === 'full' ? $capCls : 'pt-3 text-sm '.($dark ? 'text-white/60' : 'text-muted') }}">{{ $block->caption }}</figcaption>@endif
            </figure>
        @endif
        @break

    @case('spacer')
        <div class="{{ ['s' => 'h-6', 'm' => 'h-14', 'l' => 'h-28'][$block->opt('space')] ?? 'h-14' }} flex items-center">
            @if($block->opt('line') === '1')<div class="mx-auto h-px w-full max-w-[1600px] bg-line"></div>@endif
        </div>
        @break
@endswitch
</section>
