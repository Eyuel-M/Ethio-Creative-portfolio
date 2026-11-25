{{-- One Insights block on the public post page. $post gives the accent colour; settings come from PostBlock::opt(). --}}
@php
    use App\Models\PostBlock;
    use App\Support\RichText;
    $bgKey = $block->opt('bg') ?? 'none';
    $dark = $bgKey === 'dark';
    $bg = ['none' => '', 'light' => 'bg-zinc-50', 'dark' => 'bg-ink text-white', 'accent' => $post->accent('soft')][$bgKey] ?? '';
    $pad = ['none' => '', 's' => 'py-3 sm:py-4', 'm' => 'py-8 sm:py-10', 'l' => 'py-14 sm:py-20'][$block->opt('pad') ?? 'm'] ?? 'py-8';
    if ($bgKey !== 'none' && $block->opt('pad') === 's') { $pad = 'py-8 sm:py-10'; }
    $width = $block->opt('width') ?? 'narrow';
    $inner = ['narrow' => 'mx-auto max-w-[720px] px-5', 'wide' => 'mx-auto max-w-[1120px] px-5', 'full' => 'w-full px-2'][$width] ?? 'mx-auto max-w-[720px] px-5';
    $center = $block->opt('align') === 'center' ? 'text-center' : '';
    $soft = $dark ? 'text-white/75' : 'text-ink-soft';
    $muted = $dark ? 'text-white/55' : 'text-muted';
    $round = $width === 'full' ? 'rounded-xl' : 'rounded-2xl';
@endphp
<section class="{{ $bg }} {{ $pad }}" data-block="{{ $block->type }}">
@switch($block->type)
    @case('heading')
        <div class="{{ $inner }} {{ $center }}">
            @if($block->opt('level') === 'h3')
                <h3 id="{{ $block->anchor() }}" class="scroll-mt-28 pt-2 font-display text-xl font-bold tracking-tight sm:text-2xl">{{ $block->body }}</h3>
            @else
                <h2 id="{{ $block->anchor() }}" class="scroll-mt-28 pt-4 font-display text-[26px] font-bold leading-tight tracking-tight sm:text-[32px]">{{ $block->body }}</h2>
            @endif
        </div>
        @break

    @case('text')
        <div class="{{ $inner }} {{ $center }}">
            <div class="prose-post {{ $block->opt('size') === 'lg' ? 'prose-lg' : '' }} {{ $block->opt('dropcap') === '1' ? 'has-dropcap' : '' }} {{ $dark ? 'prose-dark' : '' }}" style="--accent: {{ \App\Models\Post::ACCENTS[$post->opt('accent')]['hex'] }}">{{ RichText::render($block->body) }}</div>
        </div>
        @break

    @case('image')
        @if($block->mediaUrl())
            <figure class="{{ $inner }}">
                <img src="{{ $block->mediaUrl() }}" alt="{{ $block->alt ?? '' }}" class="block h-auto w-full {{ $round }}" loading="lazy">
                @if($block->caption)<figcaption class="mx-auto max-w-[720px] px-1 pt-3 text-center text-sm {{ $muted }}">{!! RichText::inline($block->caption) !!}</figcaption>@endif
            </figure>
        @endif
        @break

    @case('gallery')
        @php
            $cols = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-2 lg:grid-cols-3', '4' => 'grid-cols-2 lg:grid-cols-4'][$block->opt('cols')] ?? 'sm:grid-cols-2';
            $gap = ['none' => 'gap-0', 's' => 'gap-2', 'm' => 'gap-4'][$block->opt('gap')] ?? 'gap-2';
        @endphp
        <figure class="{{ $inner }}">
            <div class="grid {{ $cols }} {{ $gap }}">
                @foreach($block->galleryItems() as $g)
                    <img src="{{ $g['url'] }}" alt="{{ $g['alt'] }}" class="block aspect-[4/3] w-full object-cover {{ $block->opt('gap') === 'none' ? '' : 'rounded-xl' }}" loading="lazy">
                @endforeach
            </div>
            @if($block->caption)<figcaption class="mx-auto max-w-[720px] px-1 pt-3 text-center text-sm {{ $muted }}">{!! RichText::inline($block->caption) !!}</figcaption>@endif
        </figure>
        @break

    @case('image_text')
        <div class="{{ $inner }} grid items-center gap-8 md:grid-cols-2 md:gap-12">
            @if($block->mediaUrl())
                <img src="{{ $block->mediaUrl() }}" alt="{{ $block->alt ?? '' }}" class="block h-auto w-full rounded-2xl {{ $block->opt('side') === 'right' ? 'md:order-2' : '' }}" loading="lazy">
            @endif
            <div>
                @if($block->title)<h3 class="font-display text-2xl font-bold tracking-tight">{{ $block->title }}</h3>@endif
                <div class="prose-post {{ $block->title ? 'mt-3' : '' }} {{ $dark ? 'prose-dark' : '' }}" style="--accent: {{ \App\Models\Post::ACCENTS[$post->opt('accent')]['hex'] }}">{{ RichText::render($block->body) }}</div>
            </div>
        </div>
        @break

    @case('quote')
        <figure class="{{ $inner }} {{ $center }}">
            <blockquote class="relative {{ $center ? '' : 'border-l-[3px] pl-6 '.$post->accent('border') }} font-display text-[22px] font-semibold leading-snug tracking-tight sm:text-[28px]">“{{ $block->body }}”</blockquote>
            @if($block->caption)<figcaption class="mt-4 text-sm font-medium {{ $center ? '' : 'pl-6' }} {{ $muted }}">— {{ $block->caption }}</figcaption>@endif
        </figure>
        @break

    @case('embed')
        @if($block->embedSrc())
            <figure class="{{ $inner }}">
                <div class="aspect-video w-full overflow-hidden bg-black {{ $round }}"><iframe src="{{ $block->embedSrc() }}" class="size-full" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" title="{{ $block->caption ?? 'Video' }}" sandbox="allow-scripts allow-same-origin allow-presentation"></iframe></div>
                @if($block->caption)<figcaption class="mx-auto max-w-[720px] px-1 pt-3 text-center text-sm {{ $muted }}">{{ $block->caption }}</figcaption>@endif
            </figure>
        @endif
        @break

    @case('callout')
        @php [$toneLabel, $toneIcon, $toneCls] = PostBlock::TONES[$block->opt('tone')] ?? PostBlock::TONES['info']; @endphp
        <div class="{{ $inner }}">
            <aside class="flex gap-4 rounded-2xl border p-5 sm:p-6 {{ $toneCls }}" role="note">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white/80"><x-icon :name="$toneIcon" class="size-[18px]"/></span>
                <div class="min-w-0">
                    <p class="font-semibold">{{ $block->title ?: $toneLabel }}</p>
                    @if($block->body)<div class="prose-post prose-tight mt-1 text-[15px]" style="--accent: currentColor">{{ RichText::render($block->body) }}</div>@endif
                </div>
            </aside>
        </div>
        @break

    @case('list')
        @php $items = $block->listItems(); $style = $block->opt('style'); @endphp
        <div class="{{ $inner }}">
            @if($block->title)<p class="mb-3 font-display text-lg font-bold">{{ $block->title }}</p>@endif
            <{{ $style === 'number' ? 'ol' : 'ul' }} class="space-y-3 text-[17px] leading-relaxed {{ $soft }}">
                @foreach($items as $i => $item)
                    <li class="flex gap-3">
                        @if($style === 'number')
                            <span class="grid size-7 shrink-0 place-items-center rounded-full text-[13px] font-bold tabular-nums text-white {{ $post->accent('bg') }}">{{ $i + 1 }}</span>
                        @elseif($style === 'check')
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full {{ $post->accent('soft') }} {{ $post->accent('text') }}"><x-icon name="check" class="size-4"/></span>
                        @else
                            <span class="mt-[11px] size-1.5 shrink-0 rounded-full {{ $post->accent('bg') }}"></span>
                        @endif
                        <span class="min-w-0 prose-inline" style="--accent: {{ \App\Models\Post::ACCENTS[$post->opt('accent')]['hex'] }}">{!! RichText::inline($item) !!}</span>
                    </li>
                @endforeach
            </{{ $style === 'number' ? 'ol' : 'ul' }}>
        </div>
        @break

    @case('stats')
        @php $cols = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-3', '4' => 'sm:grid-cols-2 lg:grid-cols-4'][$block->opt('cols')] ?? 'sm:grid-cols-3'; @endphp
        <div class="{{ $inner }}">
            @if($block->title)<p class="mb-5 text-[13px] font-semibold uppercase tracking-[0.14em] {{ $muted }}">{{ $block->title }}</p>@endif
            <dl class="grid grid-cols-1 gap-3 {{ $cols }}">
                @foreach($block->items ?? [] as $s)
                    <div class="flex flex-col-reverse justify-end rounded-2xl p-5 sm:p-6 {{ $dark ? 'bg-white/5 ring-1 ring-white/10' : 'bg-white ring-1 ring-line' }}">
                        <dt class="mt-1 text-sm {{ $muted }}">{{ $s['b'] ?? '' }}</dt>
                        <dd class="font-display text-4xl font-bold tracking-tight tabular-nums sm:text-[44px] {{ $dark ? 'text-white' : $post->accent('text') }}">{{ $s['a'] ?? '' }}</dd>
                    </div>
                @endforeach
            </dl>
            @if($block->caption)<p class="mt-4 text-xs {{ $muted }}">{!! RichText::inline($block->caption) !!}</p>@endif
        </div>
        @break

    @case('table')
        @php $rows = $block->tableRows(); $head = $block->opt('header') === '1' && count($rows) > 1 ? array_shift($rows) : null; @endphp
        <div class="{{ $inner }}">
            @if($block->title)<p class="mb-3 font-display text-lg font-bold">{{ $block->title }}</p>@endif
            <div class="overflow-x-auto rounded-2xl ring-1 ring-line">
                <table class="w-full min-w-[480px] text-left text-[15px]">
                    @if($head)
                        <thead class="bg-zinc-50 text-[13px] font-semibold text-ink"><tr>@foreach($head as $h)<th scope="col" class="px-4 py-3">{!! RichText::inline($h) !!}</th>@endforeach</tr></thead>
                    @endif
                    <tbody class="divide-y divide-line bg-white text-ink-soft">
                        @foreach($rows as $r)
                            <tr class="{{ $block->opt('striped') === '1' ? 'even:bg-zinc-50/70' : '' }}">@foreach($r as $ci => $c)<td class="px-4 py-3 {{ $ci === 0 ? 'font-medium text-ink' : '' }}">{!! RichText::inline($c) !!}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($block->caption)<p class="mt-3 text-xs {{ $muted }}">{!! RichText::inline($block->caption) !!}</p>@endif
        </div>
        @break

    @case('faq')
        <div class="{{ $inner }}">
            @if($block->title)<h2 class="mb-4 font-display text-2xl font-bold tracking-tight">{{ $block->title }}</h2>@endif
            <div class="divide-y {{ $dark ? 'divide-white/10' : 'divide-line' }} border-y {{ $dark ? 'border-white/10' : 'border-line' }}">
                @foreach($block->items ?? [] as $qi => $qa)
                    <details class="group py-4" @if($qi === 0 && $block->opt('open') === '1') open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-semibold [&::-webkit-details-marker]:hidden">
                            {{ $qa['a'] ?? '' }}
                            <span class="grid size-7 shrink-0 place-items-center rounded-full transition group-open:rotate-45 {{ $dark ? 'bg-white/10' : 'bg-zinc-100' }}"><x-icon name="plus" class="size-4"/></span>
                        </summary>
                        <div class="prose-post prose-tight mt-3 pr-10 {{ $dark ? 'prose-dark' : '' }}" style="--accent: {{ \App\Models\Post::ACCENTS[$post->opt('accent')]['hex'] }}">{{ RichText::render($qa['b'] ?? '') }}</div>
                    </details>
                @endforeach
            </div>
        </div>
        @break

    @case('button')
        @php
            $href = PostBlock::safeUrl($block->url);
            $btn = ['dark' => 'btn-dark', 'accent' => 'text-white '.$post->accent('bg').' hover:opacity-90', 'light' => 'btn-light'][$block->opt('variant')] ?? 'btn-dark';
            $external = $href && preg_match('~^https?://~i', $href) && ! str_starts_with($href, rtrim((string) config('app.url'), '/'));
        @endphp
        <div class="{{ $inner }} {{ $center }}">
            @if($block->title)<h3 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ $block->title }}</h3>@endif
            @if($block->body)<p class="mx-auto mt-2 max-w-xl {{ $soft }}">{!! RichText::inline($block->body) !!}</p>@endif
            @if($href && $block->caption)
                <a href="{{ $href }}" @if($external) target="_blank" rel="noopener" @endif class="btn btn-pill {{ $btn }} mt-6 h-12 px-7">{{ $block->caption }} <x-icon name="arrow-right" class="size-4"/></a>
            @endif
        </div>
        @break

    @case('download')
        @if($block->media_path)
            @php $ext = strtoupper(pathinfo($block->media_path, PATHINFO_EXTENSION)); @endphp
            <div class="{{ $inner }}">
                <a href="{{ \App\Support\Media::url($block->media_path) }}" download="{{ $block->fileName() }}" class="group flex items-center gap-4 rounded-2xl border border-line bg-white p-4 transition hover:border-zinc-300 hover:shadow-[0_14px_30px_-20px_rgba(12,12,16,.35)] sm:p-5">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl text-[11px] font-bold text-white {{ $post->accent('bg') }}">{{ $ext ?: 'FILE' }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ $block->title ?: $block->fileName() }}</span>
                        @if($block->body)<span class="block truncate text-sm text-muted">{{ $block->body }}</span>@endif
                        @if(($block->items['size'] ?? null))<span class="block text-xs text-faint">{{ $block->items['size'] }}</span>@endif
                    </span>
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-zinc-100 transition group-hover:bg-ink group-hover:text-white"><x-icon name="download" class="size-5"/></span>
                </a>
            </div>
        @endif
        @break

    @case('spacer')
        <div class="{{ ['s' => 'h-4', 'm' => 'h-10', 'l' => 'h-20'][$block->opt('space')] ?? 'h-10' }} flex items-center">
            @if($block->opt('line') === '1')<div class="mx-auto h-px w-full max-w-[720px] bg-line"></div>@endif
        </div>
        @break
@endswitch
</section>
