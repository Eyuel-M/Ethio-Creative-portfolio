{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ config('marketplace.brand') }} Insights</title>
    <link>{{ route('insights.index') }}</link>
    <atom:link href="{{ route('insights.feed') }}" rel="self" type="application/rss+xml"/>
    <description>Studies, guides, product updates and creator spotlights from {{ config('marketplace.brand') }}.</description>
    <language>en</language>
    @if($posts->first())<lastBuildDate>{{ $posts->first()->published_at->toRssString() }}</lastBuildDate>@endif
    @foreach($posts as $p)
    <item>
        <title>{{ $p->title }}</title>
        <link>{{ route('insights.show', $p) }}</link>
        <guid isPermaLink="true">{{ route('insights.show', $p) }}</guid>
        <pubDate>{{ $p->published_at->toRssString() }}</pubDate>
        <category>{{ $p->typeLabel() }}</category>
        @foreach($p->topics ?? [] as $t)<category>{{ $t }}</category>@endforeach
        <author>noreply@{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost' }} ({{ config('marketplace.brand') }})</author>
        <description>{{ $p->metaDescription() }}</description>
    </item>
    @endforeach
</channel>
</rss>
