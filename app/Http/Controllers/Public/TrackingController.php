<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    /**
     * GET /api/v1/track/{code}
     * Retourne uniquement les infos publiques (pas de données client sensibles)
     */
    public function show(string $code): JsonResponse
    {
        $code = strtoupper(trim($code));

        $project = Project::where('tracking_code', $code)
            ->with([
                'steps' => fn($q) => $q->orderBy('order')->select([
                    'id',
                    'project_id',
                    'title',
                    'description',
                    'status',
                    'order',
                    'completed_at',
                ])
            ])
            ->firstOrFail([
                'id',
                'tracking_code',
                'title',
                'type',
                'status',
                'progress',
                'started_at',
                'estimated_end_at',
            ]);

        return response()->json([
            'data' => [
                'tracking_code' => $project->tracking_code,
                'title' => $project->title,
                'type' => $project->type,
                'status' => $project->status,
                'progress' => $project->progress,
                'started_at' => $project->started_at,
                'estimated_end_at' => $project->estimated_end_at,
                'steps' => $project->steps,
            ],
        ]);
    }
}