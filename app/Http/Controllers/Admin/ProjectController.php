<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * GET /api/v1/admin/projects
     */
    public function index(Request $request): JsonResponse
    {
        $query = Project::with('client:id,name,email');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(
                fn($q) => $q
                    ->where('title', 'like', $term)
                    ->orWhere('tracking_code', 'like', $term)
            );
        }

        $projects = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($projects);
    }

    /**
     * GET /api/v1/admin/projects/{id}
     */
    public function show(string $id): JsonResponse
    {
        $project = Project::with(['client:id,name,email', 'steps' => fn($q) => $q->orderBy('order')])
            ->findOrFail($id);

        return response()->json(['data' => $project]);
    }

    /**
     * POST /api/v1/admin/projects
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['web', 'logiciel', 'saas', 'mobile', 'autre'])],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled'])],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'is_portfolio' => ['boolean'],
            'started_at' => ['nullable', 'date'],
            'estimated_end_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Génération du tracking_code : GT-YYYY-XXX
        $year = now()->year;
        $count = Project::whereYear('created_at', $year)->count() + 1;
        $validated['tracking_code'] = sprintf('GT-%d-%03d', $year, $count);
        $validated['slug'] = Str::slug($validated['title']) . '-' . strtolower($validated['tracking_code']);

        $project = Project::create($validated);

        return response()->json(['data' => $project], 201);
    }

    /**
     * PUT /api/v1/admin/projects/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'client_id' => ['sometimes', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', Rule::in(['web', 'logiciel', 'saas', 'mobile', 'autre'])],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled'])],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'is_portfolio' => ['boolean'],
            'started_at' => ['nullable', 'date'],
            'estimated_end_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Auto-set completed_at si on passe à completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$project->completed_at) {
            $validated['completed_at'] = now();
            $validated['progress'] = 100;
        }

        $project->update($validated);

        return response()->json(['data' => $project->fresh(['client:id,name,email', 'steps'])]);
    }

    /**
     * DELETE /api/v1/admin/projects/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete(); // SoftDelete

        return response()->json(['message' => 'Projet archivé.']);
    }
}