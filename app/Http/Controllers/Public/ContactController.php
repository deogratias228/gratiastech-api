<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * POST /api/v1/contact
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:100'],
            'service_interest' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $contact = Contact::create($validated);

        // TODO: Mail::to(config('mail.admin'))->queue(new NewContactMail($contact));

        return response()->json([
            'message' => 'Votre message a bien été reçu. Nous vous répondrons sous 24h.',
            'id' => $contact->id,
        ], 201);
    }
}