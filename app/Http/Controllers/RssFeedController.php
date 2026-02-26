<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $posts = Post::published()
            ->with('user')
            ->select(['id', 'user_id', 'title', 'slug', 'excerpt', 'content', 'published_at', 'updated_at'])
            ->latest('published_at')
            ->limit(20)
            ->get();

        return response()
            ->view('feed.rss', compact('posts'))
            ->header('Content-Type', 'application/rss+xml');
    }
}
