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
    public function register(Request $request)
{
    $data = $request->validate([
        'name'     => ['required','string','max:255'],
        'email'    => ['required','email','max:255','unique:users,email'],
        'password' => ['required', 'string', PasswordRule::min(6)],
    ]);

    $user = User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'password' => $data['password'], // 👈 sin Hash::make
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    return response()->json([
        'message' => 'Usuario creado',
        'token'   => $token,
        'user'    => $user,
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
}
