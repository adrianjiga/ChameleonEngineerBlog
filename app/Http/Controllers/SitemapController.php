<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $posts = Post::published()
            ->select(['slug', 'updated_at'])
            ->latest('updated_at')
            ->get();

        $categories = Category::select(['slug', 'updated_at'])
            ->latest('updated_at')
            ->get();

        return response()
            ->view('sitemap.index', compact('posts', 'categories'))
            ->header('Content-Type', 'application/xml');
    }
}
