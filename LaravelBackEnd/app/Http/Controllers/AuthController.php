<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'usuario' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $usuario = trim($validated['usuario']);

        $user = User::query()
            ->where('email', $usuario)
            ->orWhere('name', $usuario)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => 'Credenciales incorrectas.',
            ], 401);
        }

        $plainToken = Str::random(80);

        $token = ApiToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(12),
            'last_used_at' => now(),
        ]);

        return response()->json([
            'message' => 'Inicio de sesion correcto.',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('api_token');

        if ($token instanceof ApiToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }
}
