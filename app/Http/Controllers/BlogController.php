<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $categorySlug = $request->string('category')->toString();
        $page = $request->integer('page', 1);
        $version = Cache::get('blog:index:version', 0);

        $cacheKey = "blog:index:{$version}:{$page}:{$search}:{$categorySlug}";

        $posts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($search, $categorySlug) {
            return Post::published()
                ->with(['categories', 'user'])
                ->when($search, fn ($q) => $q->search($search))
                ->when($categorySlug, fn ($q) => $q->whereHas(
                    'categories',
                    fn ($q) => $q->where('slug', $categorySlug)
                ))
                ->latest('published_at')
                ->paginate(15)
                ->withQueryString();
        });

        $categories = Category::withCount('posts')
            ->orderByDesc('posts_count')
            ->get();

        return Inertia::render('blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
            ],
        ]);
    }

    public function show(Post $post): Response
    {
        abort_if($post->status !== PostStatus::Published, 404);

        $cacheKey = "post:{$post->id}";

        $post = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $post->load('categories', 'user'));

        $relatedPosts = Post::published()
            ->whereHas('categories', fn ($q) => $q->whereIn(
                'categories.id',
                $post->categories->pluck('id')
            ))
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get();

        return Inertia::render('blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
