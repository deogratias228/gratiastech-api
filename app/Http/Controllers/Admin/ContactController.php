<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * GET /api/v1/admin/contacts
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contact::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(
                fn($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('subject', 'like', $term)
            );
        }

        $contacts = $query->orderByDesc('created_at')->paginate(25);

        return response()->json($contacts);
    }

    /**
     * GET /api/v1/admin/contacts/{id}
     */
    public function show(string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);

        // Marquer comme lu automatiquement
        if ($contact->status === 'new') {
            $contact->update(['status' => 'read']);
        }

        return response()->json(['data' => $contact]);
    }

    /**
     * PUT /api/v1/admin/contacts/{id}
     * Mise à jour du statut (read / replied / archived)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'read', 'replied', 'archived'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $contact->update($validated);

        return response()->json(['data' => $contact->fresh(), 'message' => 'Statut mis à jour.']);
    }

    /**
     * DELETE /api/v1/admin/contacts/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Contact::findOrFail($id)->delete();

        return response()->json(['message' => 'Demande supprimée.']);
    }
}