<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consent;
use Illuminate\Http\Request;
use Exception;

class ConsentController extends Controller
{
    /**
     * Muestra todos los consentimientos del usuario autenticado
     */
    public function index(Request $request)
    {
        try {
            $consents = Consent::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Consentimientos obtenidos correctamente.',
                'data' => $consents
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener consentimientos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica si el usuario tiene algún consentimiento aceptado
     */
    public function check(Request $request)
    {
        try {
            $hasConsent = Consent::where('user_id', $request->user()->id)
                ->whereNotNull('accepted_at')
                ->exists();

            return response()->json([
                'has_consent' => $hasConsent
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al verificar consentimiento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda un nuevo consentimiento
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'version' => 'required|string|max:20',
                'accepted_at' => 'required|date',
                'signature_path' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            // Verificar si ya existe un consentimiento para esta versión
            $existingConsent = Consent::where('user_id', $request->user()->id)
                ->where('version', $validated['version'])
                ->first();

            if ($existingConsent) {
                return response()->json([
                    'message' => 'Ya existe un consentimiento para esta versión.',
                    'data' => $existingConsent
                ], 200);
            }

            $consent = Consent::create([
                'user_id' => $request->user()->id, // ✅ Consistente con check()
                'version' => $validated['version'],
                'accepted_at' => $validated['accepted_at'],
                'signature_path' => $validated['signature_path'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'message' => 'Consentimiento registrado correctamente.',
                'data' => $consent
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar consentimiento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra un consentimiento por ID (solo si pertenece al usuario)
     */
    public function show(Request $request, $id)
    {
        try {
            $consent = Consent::where('id', $id)
                ->where('user_id', $request->user()->id) // ✅ Consistente
                ->firstOrFail();

            return response()->json([
                'message' => 'Consentimiento obtenido correctamente.',
                'data' => $consent
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Consentimiento no encontrado.',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}