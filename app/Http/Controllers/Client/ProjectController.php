<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * GET /api/v1/client/projects
     * Liste les projets du client connecté
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()
            ->projects()
            ->with(['steps' => fn($q) => $q->orderBy('order')])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'tracking_code',
                'title',
                'type',
                'status',
                'progress',
                'started_at',
                'estimated_end_at',
                'tech_stack',
                'created_at',
            ]);

        return response()->json(['data' => $projects]);
    }

    /**
     * GET /api/v1/client/projects/{id}
     * Détail complet d'un projet (appartenant au client connecté)
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $project = $request->user()
            ->projects()
            ->with(['steps' => fn($q) => $q->orderBy('order')])
            ->findOrFail($id);

        return response()->json(['data' => $project]);
    }
}