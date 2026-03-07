<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $postQuery = $isAdmin ? Post::query() : Post::forUser($user);

        $stats = [
            'totalPosts' => (clone $postQuery)->count(),
            'publishedPosts' => (clone $postQuery)->where('status', PostStatus::Published)->count(),
            'draftPosts' => (clone $postQuery)->where('status', PostStatus::Draft)->count(),
            'totalCategories' => Category::count(),
            'totalViews' => (clone $postQuery)->sum('views'),
        ];

        $recentPosts = (clone $postQuery)
            ->with('categories')
            ->latest()
            ->limit(5)
            ->get();

        $popularCategories = Category::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'popularCategories' => $popularCategories,
        ]);
    }
}
