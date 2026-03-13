<?php

use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\PortfolioController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Public\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques (sans authentification)
|--------------------------------------------------------------------------
*/

// Auth
Route::post('auth/login', [AuthController::class, 'login']);

// Services
Route::get('services', [ServiceController::class, 'index']);

// Portfolio / Réalisations
Route::get('portfolio', [PortfolioController::class, 'index']);
Route::get('portfolio/{id}', [PortfolioController::class, 'show']);

// Blog
Route::get('articles', [ArticleController::class, 'index']);
Route::get('articles/{slug}', [ArticleController::class, 'show']);

// Contact
Route::post('contact', [ContactController::class, 'store']);

// Suivi de projet (code public, aucun compte requis)
Route::get('track/{code}', [TrackingController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Routes authentifiées (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    /*
    | Espace client — accès à ses propres projets uniquement
    */
    Route::prefix('client')->group(function () {
        Route::get('projects', function () {
            $projects = request()->user()
                ->projects()
                ->with('steps')
                ->latest()
                ->get()
                ->map->toPublicTracking();

            return response()->json(['data' => $projects]);
        });

        Route::get('projects/{id}', function (string $id) {
            $project = request()->user()
                ->projects()
                ->with('steps')
                ->findOrFail($id);

            return response()->json(['data' => $project->toPublicTracking()]);
        });
    });

    /*
    | Administration — rôle admin requis
    */
    Route::prefix('admin')
        ->middleware('admin')
        ->group(function () {

            // Projets + étapes
            Route::apiResource('projects', AdminProjectController::class);
            Route::patch(
                'projects/{project}/steps/{step}',
                [AdminProjectController::class, 'updateStep']
            );

            // Clients
            Route::get('clients', function () {
                $clients = \App\Models\User::where('role', 'client')
                    ->withCount('projects')
                    ->latest()
                    ->paginate(20);
                return response()->json($clients);
            });

            Route::post('clients', function () {
                $data = request()->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users',
                    'password' => 'required|string|min:8',
                    'phone' => 'nullable|string',
                    'company' => 'nullable|string',
                ]);
                $client = \App\Models\User::create([...$data, 'role' => 'client']);
                return response()->json(['data' => $client], 201);
            });

            // Contacts reçus
            Route::get('contacts', function () {
                return response()->json(
                    \App\Models\Contact::latest()->paginate(20)
                );
            });

            Route::patch('contacts/{contact}', function (\App\Models\Contact $contact) {
                $contact->update(request()->only('status', 'admin_notes'));
                return response()->json(['data' => $contact->fresh()]);
            });

            // Articles
            Route::apiResource('articles', \App\Http\Controllers\Admin\ArticleController::class);
        });
});
