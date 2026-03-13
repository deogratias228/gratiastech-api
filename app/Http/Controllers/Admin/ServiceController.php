<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * GET /api/v1/admin/services
     */
    public function index(): JsonResponse
    {
        $services = Service::orderBy('sort_order')->get();

        return response()->json(['data' => $services]);
    }

    /**
     * POST /api/v1/admin/services
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:100'],
            'tagline'     => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'features'    => ['nullable', 'array'],
            'features.*'  => ['string', 'max:120'],
            'tech_stack'  => ['nullable', 'array'],
            'tech_stack.*'=> ['string', 'max:50'],
            'icon'        => ['nullable', 'string', 'max:60'],
            'sort_order'  => ['sometimes', 'integer'],
            'is_active'   => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        $service = Service::create($validated);

        return response()->json(['data' => $service], 201);
    }

    /**
     * PUT /api/v1/admin/services/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'title'       => ['sometimes', 'string', 'max:100'],
            'tagline'     => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'features'    => ['nullable', 'array'],
            'features.*'  => ['string', 'max:120'],
            'tech_stack'  => ['nullable', 'array'],
            'tech_stack.*'=> ['string', 'max:50'],
            'icon'        => ['nullable', 'string', 'max:60'],
            'sort_order'  => ['sometimes', 'integer'],
            'is_active'   => ['boolean'],
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $service->update($validated);

        return response()->json(['data' => $service->fresh()]);
    }

    /**
     * DELETE /api/v1/admin/services/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Service::findOrFail($id)->delete();

        return response()->json(['message' => 'Service supprimé.']);
    }
}