<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    /**
     * roles attendus : 'locataire', 'proprietaire', 'les deux'
     * Utilisation : route->middleware('role:proprietaire')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(403, 'Non authentifié.');
        }
        // Si l'utilisateur a le rôle exact ou "les deux"
        if (in_array($user->role, $roles) || $user->role === 'les deux') {
            return $next($request);
        }
        abort(403, 'Accès refusé.');
    }
}
