<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::with('client:id,name,email,company')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15);

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'        => 'required|exists:users,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'type'             => 'required|in:web_development,software,saas,maintenance,other',
            'started_at'       => 'nullable|date',
            'estimated_end_at' => 'nullable|date|after_or_equal:started_at',
            'tech_stack'       => 'nullable|array',
            'internal_notes'   => 'nullable|string',
            'steps'            => 'nullable|array',
            'steps.*.title'    => 'required_with:steps|string',
            'steps.*.description' => 'nullable|string',
        ]);

        $project = Project::create([
            ...$validated,
            'tracking_code' => Project::generateTrackingCode(),
            'status'        => 'draft',
            'progress'      => 0,
        ]);

        // Créer les étapes si fournies
        if (! empty($validated['steps'])) {
            foreach ($validated['steps'] as $index => $step) {
                $project->steps()->create([
                    'title'       => $step['title'],
                    'description' => $step['description'] ?? null,
                    'order'       => $index + 1,
                    'status'      => 'pending',
                ]);
            }
        }

        return response()->json([
            'message' => 'Projet créé avec succès.',
            'data'    => $project->load('steps'),
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'data' => $project->load(['client:id,name,email,company', 'steps']),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'status'           => 'sometimes|in:draft,in_progress,on_hold,completed,cancelled',
            'progress'         => 'sometimes|integer|min:0|max:100',
            'estimated_end_at' => 'nullable|date',
            'tech_stack'       => 'nullable|array',
            'internal_notes'   => 'nullable|string',
            'is_portfolio'     => 'sometimes|boolean',
            'portfolio_data'   => 'nullable|array',
        ]);

        // Auto-complétion date si statut completed
        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
            $validated['progress']     = 100;
        }

        $project->update($validated);

        return response()->json([
            'message' => 'Projet mis à jour.',
            'data'    => $project->fresh('steps'),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Projet supprimé.']);
    }

    // Mise à jour d'une étape
    public function updateStep(Request $request, Project $project, ProjectStep $step): JsonResponse
    {
        abort_unless($step->project_id === $project->id, 403);

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'status'       => 'sometimes|in:pending,in_progress,completed,skipped',
            'order'        => 'sometimes|integer|min:1',
            'started_at'   => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = $validated['completed_at'] ?? now()->toDateString();
        }

        $step->update($validated);

        // Recalcul automatique de la progression du projet
        $this->recalculateProgress($project);

        return response()->json([
            'message' => 'Étape mise à jour.',
            'data'    => $step->fresh(),
        ]);
    }

    private function recalculateProgress(Project $project): void
    {
        $steps = $project->steps;
        if ($steps->isEmpty()) return;

        $completed = $steps->where('status', 'completed')->count();
        $total     = $steps->whereNotIn('status', ['skipped'])->count();

        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $project->update(['progress' => $progress]);
    }
}