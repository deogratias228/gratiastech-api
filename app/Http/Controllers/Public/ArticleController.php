<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * GET /api/v1/articles
     * ?page=1&category=dev|design|seo
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::where('status', 'published')
            ->with('author:id,name');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $articles = $query
            ->orderByDesc('published_at')
            ->paginate(12, [
                'id',
                'slug',
                'title',
                'excerpt',
                'category',
                'thumbnail',
                'reading_time',
                'published_at',
                'author_id',
            ]);

        return response()->json($articles);
    }

    /**
     * GET /api/v1/articles/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)
            ->where('status', 'published')
            ->with('author:id,name')
            ->firstOrFail();

        // Incrémenter le compteur de vues en tâche de fond
        $article->increment('views');

        return response()->json(['data' => $article]);
    }
}