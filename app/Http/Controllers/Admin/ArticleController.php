<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    /**
     * GET /api/v1/admin/articles
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::with('author:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $articles = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($articles);
    }

    /**
     * GET /api/v1/admin/articles/{id}
     */
    public function show(string $id): JsonResponse
    {
        $article = Article::with('author:id,name')->findOrFail($id);

        return response()->json(['data' => $article]);
    }

    /**
     * POST /api/v1/admin/articles
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['required', 'string', 'max:400'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'max:60'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'reading_time' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        $validated['author_id'] = $request->user()->id;

        // Auto-calcul reading_time (~200 mots/min)
        if (!isset($validated['reading_time'])) {
            $wordCount = str_word_count(strip_tags($validated['content']));
            $validated['reading_time'] = max(1, (int) ceil($wordCount / 200));
        }

        if (($validated['status'] ?? 'draft') === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $article = Article::create($validated);

        return response()->json(['data' => $article], 201);
    }

    /**
     * PUT /api/v1/admin/articles/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'excerpt' => ['sometimes', 'string', 'max:400'],
            'content' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', 'max:60'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'reading_time' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
        ]);

        // Auto-set published_at à la première publication
        if (
            isset($validated['status']) && $validated['status'] === 'published'
            && $article->status !== 'published' && empty($validated['published_at'])
        ) {
            $validated['published_at'] = now();
        }

        $article->update($validated);

        return response()->json(['data' => $article->fresh('author:id,name')]);
    }

    /**
     * DELETE /api/v1/admin/articles/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Article::findOrFail($id)->delete();

        return response()->json(['message' => 'Article supprimé.']);
    }
}