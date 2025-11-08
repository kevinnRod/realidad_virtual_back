<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Attributes\Middleware;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\JsonResponse;

// #[Middleware(IsAdmin::class, only: ['index', 'store', 'update', 'destroy'])]
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // 🔍 Filtro por nombre o email
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 📄 Paginación (10 por página)
        $users = $query->orderBy('name')->paginate(10);

        return response()->json([
            'message' => 'Usuarios paginados',
            'data' => $users
        ]);
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'      => 'required|string|max:191',
                'email'     => 'required|email|unique:users',
                'password'  => 'required|string|min:8',
                'birthdate' => 'required|date',
                'sex'       => 'required|in:M,F,O',
                'role'      => 'required|string|in:student,patient,researcher,therapist,admin',
                'is_admin'  => 'boolean'
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $user = User::create($validated);

            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'data' => $user
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function show($id) {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'message' => 'Usuario obtenido correctamente.',
                'data' => $user
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e, 404, 'Usuario no encontrado.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name'      => 'sometimes|string|max:191',
                'email'     => 'sometimes|email|unique:users,email,' . $id,
                'password'  => 'sometimes|string|min:8',
                'birthdate' => 'sometimes|date',
                'sex'       => 'sometimes|in:M,F,O',
                'role'      => 'sometimes|string|in:student,patient,researcher,therapist,admin',
                'is_admin'  => 'sometimes|boolean'
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'data' => $user
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse($e, 404, 'No se pudo actualizar el usuario.');
        }
    }

public function destroy($id): JsonResponse
{
    try {
        $user = User::findOrFail($id); // asegúrate que no estás usando User::onlyTrashed()

        $user->delete(); // Esto debería marcar deleted_at
        return response()->json([
            'message' => 'Usuario eliminado correctamente.'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'No se pudo eliminar el usuario.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function errorResponse(Exception $e, int $status = 500, string $defaultMessage = 'Ocurrió un error inesperado.') {
        return response()->json([
            'message' => $defaultMessage,
            'error' => config('app.debug') ? $e->getMessage() : null
        ], $status);
    }

    public function updateProfile(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'name' => 'required|string|max:191',
        'birthdate' => 'nullable|date',
        'sex' => 'nullable|in:M,F,O',
        'role' => 'nullable|in:student,admin',
    ]);

    $user->update($request->only(['name', 'birthdate', 'sex', 'role']));

    return response()->json([
        'message' => 'Perfil actualizado correctamente',
        'data' => $user->fresh()  
    ]);
}

    public function me(Request $request)
{
    $user = $request->user();
    
    // Asegúrate de incluir is_admin en la respuesta
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'is_admin' => $user->is_admin, 
        'role' => $user->role,
        'birthdate' => $user->birthdate,
        'sex' => $user->sex,
    ]);
}

/**
 * Lista usuarios para filtros de analytics (endpoint público)
 * GET /api/users/for-filters
 */
/**
 * Lista usuarios para filtros de analytics (solo admin)
 * GET /api/users/for-filters
 */
public function forFilters(Request $request)
{
    $query = User::query()
        ->select('id', 'code', 'name', 'email', 'role')
        ->whereNull('deleted_at');

    // Opcional: Solo usuarios con sesiones VR
    if ($request->has('with_sessions')) {
        $query->whereHas('vrSessions');
    }

    $users = $query->orderBy('name')->get();

    // Transformar para frontend
    $users = $users->map(function($user) {
        $displayLabel = $user->name;
        
        if (!$displayLabel || trim($displayLabel) === '') {
            $displayLabel = $user->email;
        }
        
        if (!$displayLabel || trim($displayLabel) === '') {
            $displayLabel = $user->code;
        }
        
        if (!$displayLabel || trim($displayLabel) === '') {
            $displayLabel = "USER_" . str_pad($user->id, 4, '0', STR_PAD_LEFT);
        }

        return [
            'id' => $user->id,
            'code' => $user->code,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'display_label' => $displayLabel
        ];
    });

    return response()->json($users);
}

/**
 * Lista solo usuarios que tienen sesiones VR
 * GET /api/users/with-sessions
 */
public function withSessions(Request $request)
{
    $users = User::query()
        ->select('users.id', 'users.code', 'users.name', 'users.email', 'users.role')
        ->join('vr_sessions', 'users.id', '=', 'vr_sessions.user_id')
        ->whereNull('users.deleted_at')
        ->distinct()
        ->orderBy('users.name')
        ->get();

    $users = $users->map(function($user) {
        $displayLabel = $user->name ?: ($user->email ?: ($user->code ?: "USER_" . str_pad($user->id, 4, '0', STR_PAD_LEFT)));

        return [
            'id' => $user->id,
            'code' => $user->code,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'display_label' => $displayLabel
        ];
    });

    return response()->json($users);
}

}
