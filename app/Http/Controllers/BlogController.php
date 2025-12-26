<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Web: Display all blog posts (Blade view)
     */
    public function webIndex(Request $request): View
    {
        $blogs = Blog::published()
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return view('blog.index', compact('blogs'));
    }

    /**
     * Web: Display a single blog post (Blade view)
     */
    public function webShow(string $slug): View
    {
        $blog = Blog::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('blog.show', compact('blog'));
    }

    /**
     * API: Get all published blog posts
     */
    public function index(Request $request): JsonResponse
    {
        $blogs = Blog::published()
            ->orderBy('published_at', 'desc')
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'featured_image',
                'author',
                'category',
                'tags',
                'read_time',
                'published_at',
            ])
            ->paginate($request->get('per_page', 10));

        return response()->json($blogs);
    }

    /**
     * Get a single blog post by slug
     */
    public function show(string $slug): JsonResponse
    {
        $blog = Blog::published()
            ->where('slug', $slug)
            ->first();

        if (!$blog) {
            return response()->json([
                'error' => 'Blog post not found',
            ], 404);
        }

        return response()->json([
            'blog' => $blog,
        ]);
    }

    /**
     * Get recent blog posts (for sidebar/footer)
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 3);

        $blogs = Blog::published()
            ->orderBy('published_at', 'desc')
            ->select(['id', 'title', 'slug', 'excerpt', 'published_at', 'read_time'])
            ->limit($limit)
            ->get();

        return response()->json([
            'blogs' => $blogs,
        ]);
    }

    /**
     * Get blog posts by category
     */
    public function byCategory(string $category): JsonResponse
    {
        $blogs = Blog::published()
            ->where('category', $category)
            ->orderBy('published_at', 'desc')
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'featured_image',
                'author',
                'read_time',
                'published_at',
            ])
            ->get();

        return response()->json([
            'blogs' => $blogs,
            'category' => $category,
        ]);
    }
}
