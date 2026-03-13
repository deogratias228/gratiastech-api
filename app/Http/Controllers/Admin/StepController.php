<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StepController extends Controller
{
    /**
     * GET /api/v1/admin/projects/{projectId}/steps
     */
    public function index(string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $steps = $project->steps()->orderBy('order')->get();

        return response()->json(['data' => $steps]);
    }

    /**
     * POST /api/v1/admin/projects/{projectId}/steps
     */
    public function store(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'skipped'])],
            'order' => ['sometimes', 'integer', 'min:1'],
        ]);

        // Auto-order si non fourni
        if (!isset($validated['order'])) {
            $validated['order'] = $project->steps()->max('order') + 1;
        }

        $step = $project->steps()->create($validated);

        return response()->json(['data' => $step], 201);
    }

    /**
     * PUT /api/v1/admin/projects/{projectId}/steps/{stepId}
     */
    public function update(Request $request, string $projectId, string $stepId): JsonResponse
    {
        $step = ProjectStep::where('project_id', $projectId)->findOrFail($stepId);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'skipped'])],
            'order' => ['sometimes', 'integer', 'min:1'],
            'completed_at' => ['nullable', 'date'],
        ]);

        // Auto-set completed_at
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$step->completed_at) {
            $validated['completed_at'] = now();
        }

        $step->update($validated);

        // Recalcul progress du projet parent
        $this->recalculateProjectProgress($projectId);

        return response()->json(['data' => $step->fresh()]);
    }

    /**
     * DELETE /api/v1/admin/projects/{projectId}/steps/{stepId}
     */
    public function destroy(string $projectId, string $stepId): JsonResponse
    {
        $step = ProjectStep::where('project_id', $projectId)->findOrFail($stepId);
        $step->delete();

        $this->recalculateProjectProgress($projectId);

        return response()->json(['message' => 'Étape supprimée.']);
    }

    /**
     * PUT /api/v1/admin/projects/{projectId}/steps/reorder
     * Body: { "order": [stepId1, stepId2, ...] }
     */
    public function reorder(Request $request, string $projectId): JsonResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        Project::findOrFail($projectId);

        foreach ($request->order as $position => $stepId) {
            ProjectStep::where('project_id', $projectId)
                ->where('id', $stepId)
                ->update(['order' => $position + 1]);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function recalculateProjectProgress(string $projectId): void
    {
        $steps = ProjectStep::where('project_id', $projectId)
            ->whereIn('status', ['completed', 'skipped', 'in_progress', 'pending'])
            ->get(['status']);

        $total = $steps->count();
        $completed = $steps->whereIn('status', ['completed', 'skipped'])->count();

        if ($total > 0) {
            $progress = (int) round(($completed / $total) * 100);
            Project::where('id', $projectId)->update(['progress' => $progress]);
        }
    }
}