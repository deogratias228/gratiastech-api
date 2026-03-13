<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    /**
     * GET /api/v1/portfolio
     * Filtres : ?type=web|logiciel|saas&page=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = Project::where('is_portfolio', true)
            ->where('status', 'completed')
            ->with('client:id,name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $projects = $query
            ->orderByDesc('completed_at')
            ->paginate(9, [
                'id',
                'slug',
                'title',
                'description',
                'type',
                'tech_stack',
                'thumbnail',
                'completed_at',
                'client_id',
            ]);

        return response()->json($projects);
    }

    /**
     * GET /api/v1/portfolio/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $project = Project::where('slug', $slug)
            ->where('is_portfolio', true)
            ->with(['client:id,name', 'steps' => fn($q) => $q->orderBy('order')])
            ->firstOrFail();

        return response()->json(['data' => $project]);
    }
}