<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    /**
     * GET /api/v1/services
     */
    public function index(): JsonResponse
    {
        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->get([
                'id', 'slug', 'title', 'tagline', 'description',
                'features', 'tech_stack', 'icon', 'sort_order',
            ]);

        return response()->json(['data' => $services]);
    }

    /**
     * GET /api/v1/services/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $service = Service::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json(['data' => $service]);
    }
}