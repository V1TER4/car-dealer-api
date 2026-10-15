<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        \Log::info('============= CHEGOU AQUI =============');
        \Log::info($request->all());
        \Log::info('============= CHEGOU AQUI =============');
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            \Log::info('Falha de login', ['email' => $credentials['email'], 'ip' => $request->ip()]);
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        $token = $user->createToken('vehicle-platform-web')->plainTextToken;
        $expiration = config('sanctum.expiration');

        return response()->json([
            'token' => $token,
            'expires_at' => $expiration ? Carbon::now()->addMinutes($expiration)->toISOString() : null,
            'user' => $user->load('stores'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->load('stores')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }
}
