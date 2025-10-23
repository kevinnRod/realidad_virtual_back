<?php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
// ❌ use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule; // ✅

class AuthController extends Controller
{
// app/Http/Controllers/AuthController.php
// app/Http/Controllers/AuthController.php
// app/Http/Controllers/AuthController.php
public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'birthdate' => 'required|date',
        'sex' => 'required|in:M,F,O',
        'role' => 'required|string|in:student,patient,researcher,therapist', // ✅ SIN admin
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'birthdate' => $validated['birthdate'],
        'sex' => $validated['sex'],
        'role' => $validated['role'],
        'is_admin' => false, // ✅ Siempre false
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user
    ], 201);
}

    public function login(Request $r)
    {
        $data = $r->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }



    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    // En app/Http/Controllers/Api/AuthController.php

public function me(Request $request)
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // ✅ SIN relaciones por ahora
        return response()->json($user);
        
    } catch (\Exception $e) {
        
        return response()->json([
            'message' => 'Error al obtener usuario',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
