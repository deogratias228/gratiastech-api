<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * GET /api/v1/admin/clients
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'client')->withCount('projects');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(
                fn($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company', 'like', $term)
            );
        }

        $clients = $query
            ->orderByDesc('created_at')
            ->paginate(20, ['id', 'name', 'email', 'company', 'phone', 'created_at']);

        return response()->json($clients);
    }

    /**
     * GET /api/v1/admin/clients/{id}
     */
    public function show(string $id): JsonResponse
    {
        $client = User::where('role', 'client')
            ->with([
                'projects' => fn($q) => $q->select([
                    'id',
                    'client_id',
                    'tracking_code',
                    'title',
                    'status',
                    'progress',
                    'created_at',
                ])->orderByDesc('created_at')
            ])
            ->findOrFail($id);

        return response()->json(['data' => $client]);
    }

    /**
     * POST /api/v1/admin/clients
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'company' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $client = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => 'client',
        ]);

        return response()->json([
            'data' => $client->only(['id', 'name', 'email', 'company', 'role']),
            'message' => 'Client créé avec succès.',
        ], 201);
    }

    /**
     * PUT /api/v1/admin/clients/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $client = User::where('role', 'client')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($client->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'company' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $client->update($validated);

        return response()->json(['data' => $client->fresh(), 'message' => 'Client mis à jour.']);
    }

    /**
     * DELETE /api/v1/admin/clients/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $client = User::where('role', 'client')->findOrFail($id);
        $client->delete();

        return response()->json(['message' => 'Client supprimé.']);
    }
}