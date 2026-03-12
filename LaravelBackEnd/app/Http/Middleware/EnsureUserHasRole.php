<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param string ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return new JsonResponse([
                'error' => 'No autenticado.',
            ], 401);
        }

        if (!empty($roles) && !in_array($user->rol, $roles, true)) {
            return new JsonResponse([
                'error' => 'No tienes permisos para realizar esta accion.',
            ], 403);
        }

        return $next($request);
    }
}
