<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenIsValid
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return new JsonResponse([
                'error' => 'No autenticado.',
            ], 401);
        }

        $tokenHash = hash('sha256', $bearerToken);

        $apiToken = ApiToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$apiToken || ($apiToken->expires_at && $apiToken->expires_at->isPast())) {
            return new JsonResponse([
                'error' => 'Token invalido o expirado.',
            ], 401);
        }

        $apiToken->update([
            'last_used_at' => now(),
        ]);

        $request->setUserResolver(function () use ($apiToken) {
            return $apiToken->user;
        });

        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
