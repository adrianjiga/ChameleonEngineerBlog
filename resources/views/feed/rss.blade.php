<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }}</title>
        <link>{{ route('blog.index') }}</link>
        <description>The latest posts from {{ config('app.name') }}</description>
        <language>en-us</language>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ route('blog.show', $post->slug) }}</link>
            <description><![CDATA[{{ $post->excerpt ?? strip_tags($post->content) }}]]></description>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
            <guid isPermaLink="true">{{ route('blog.show', $post->slug) }}</guid>
            @if ($post->user)
            <author>{{ $post->user->email }} ({{ $post->user->name }})</author>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
