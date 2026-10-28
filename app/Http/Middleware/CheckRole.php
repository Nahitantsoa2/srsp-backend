<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Vérifie que l'utilisateur connecté possède l'un des rôles autorisés.
     *
     * Usage : Route::middleware('role:chef_service,administrateur')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (! in_array($user->role, $roles)) {
            return response()->json(['message' => 'Accès refusé : rôle insuffisant.'], 403);
        }

        return $next($request);
    }
}
