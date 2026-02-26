<?php

namespace App\Http\Controllers;

use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ImageOptimizer $imageOptimizer) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $posts = Post::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->forUser($user))
            ->when($search, fn ($q) => $q->search($search))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with('categories')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('posts/Index', [
            'posts' => $posts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('posts/Create', [
            'categories' => Category::all(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->imageOptimizer->optimize(
                $request->file('featured_image')
            );
        }

        $post = $request->user()->posts()->create($data);

        if (! empty($data['category_ids'])) {
            $post->categories()->sync($data['category_ids']);
        }

        return redirect()->route('posts.index')
            ->with('flash.success', 'Post created successfully.');
    }

    public function show(Post $post): Response
    {
        $this->authorize('view', $post);

        return Inertia::render('posts/Show', [
            'post' => $post->load('categories', 'user'),
        ]);
    }

    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        return Inertia::render('posts/Edit', [
            'post' => $post->load('categories'),
            'categories' => Category::all(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->imageOptimizer->optimize(
                $request->file('featured_image')
            );
        }

        $post->update($data);

        if (array_key_exists('category_ids', $data)) {
            $post->categories()->sync($data['category_ids'] ?? []);
        }

        return redirect()->route('posts.index')
            ->with('flash.success', 'Post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index')
            ->with('flash.success', 'Post deleted successfully.');
    }

    public function autosave(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return response()->json(['saved' => true]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'file', 'image', 'max:5120']]);

        $this->authorize('create', Post::class);

        $path = $this->imageOptimizer->optimize($request->file('image'));

        return response()->json(['url' => $path]);
    }
}
