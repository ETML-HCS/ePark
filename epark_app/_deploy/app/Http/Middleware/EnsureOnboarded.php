<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware simplifié pour l'onboarding.
 * 
 * L'onboarding consiste uniquement à choisir un site favori.
 * Tous les utilisateurs passent par l'onboarding à leur première connexion.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admin toujours autorisé
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Si l'utilisateur a déjà complété l'onboarding
        if ($user->onboarded) {
            // Empêcher de retourner sur l'onboarding
            if ($request->routeIs('onboarding.*')) {
                return redirect()->route('dashboard');
            }
            return $next($request);
        }

        // L'utilisateur n'a pas fait l'onboarding
        // Condition simple : a-t-il un site favori défini ?
        if ($user->favorite_site_id) {
            // Marquer comme onboardé
            $user->onboarded = true;
            $user->save();

            if ($request->routeIs('onboarding.*')) {
                return redirect()->route('dashboard');
            }
            return $next($request);
        }

        // Pas de site favori = doit faire l'onboarding
        if ($request->routeIs('onboarding.*')) {
            return $next($request);
        }

        return redirect()->route('onboarding.index');
    }
}
