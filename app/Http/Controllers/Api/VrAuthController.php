<?php

// app/Http/Controllers/Api/VrAuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginCode;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VrAuthController extends Controller
{

    public function generateCode(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->firstOrFail();

    // 🛑 Verificar si el usuario ya tiene un código activo y no usado
    $existing = LoginCode::where('user_id', $user->id)
        ->whereNull('used_at')
        ->where('expires_at', '>', now())
        ->first();

    if ($existing) {
        return response()->json([
            'message' => 'Ya tienes un código activo. Usa el actual o espera a que expire.',
            'expires_at' => $existing->expires_at
        ], 429);
    }

    do {
        $code = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $collision = LoginCode::where('code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();
    } while ($collision);

    $loginCode = LoginCode::create([
        'user_id'    => $user->id,
        'code'       => $code,
        'expires_at' => now()->addMinutes(10),
    ]);

    return response()->json([
        'message' => 'Código generado exitosamente',
        'code' => $code, 
        'expires_at' => $loginCode->expires_at
    ]);
}

public function loginWithCode(Request $request)
{
    try {
        $request->validate([
            'code' => 'required|string|size:5'
        ]);

        $loginCode = LoginCode::where('code', $request->code)
            ->latest()
            ->first();

        if (!$loginCode) {
            return response()->json(['message' => 'Código inválido'], 401);
        }

        if ($loginCode->expires_at->isPast()) {
            return response()->json(['message' => 'El código ha expirado'], 401);
        }

        if (!is_null($loginCode->used_at)) {
            return response()->json(['message' => 'El código ya fue utilizado'], 401);
        }

        $loginCode->update(['used_at' => now()]);

        $user = $loginCode->user;

        $token = $user->createToken('vr-login')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ]);
    } catch (\Throwable $e) {
        // ⛔ Ver en el log
        \Log::error('Error en loginWithCode: ' . $e->getMessage());
        return response()->json(['message' => 'Error interno'], 500);
    }
}

public function checkCodeStatus(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->firstOrFail();

    $code = LoginCode::where('user_id', $user->id)
        ->whereNull('used_at')
        ->where('expires_at', '>', now())
        ->latest()
        ->first();

    if (!$code) {
        return response()->json(['active' => false]);
    }

    return response()->json([
        'active' => true,
        'code' => $code->code,
        'expires_at' => $code->expires_at
    ]);
}


}
