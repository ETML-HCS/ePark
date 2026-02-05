<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur d'onboarding simplifié.
 * 
 * L'utilisateur a deux choix :
 * 1. Choisir un site existant comme favori
 * 2. Créer un nouveau site et le définir comme favori
 */
class OnboardingController extends Controller
{
    /**
     * Affiche la page d'onboarding.
     */
    public function index(Request $request): View
    {
        // Récupérer TOUS les sites disponibles (pas seulement ceux de l'utilisateur)
        $sites = Site::with('user:id,name')
            ->orderBy('nom')
            ->get();

        return view('onboarding.index', [
            'sites' => $sites,
        ]);
    }

    /**
     * Enregistre le choix de l'utilisateur.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'action' => 'required|in:choose,create',
            // Pour le choix d'un site existant
            'site_id' => 'required_if:action,choose|nullable|exists:sites,id',
            // Pour la création d'un nouveau site
            'site_nom' => 'required_if:action,create|nullable|string|max:255',
            'site_adresse' => 'required_if:action,create|nullable|string|max:255',
        ]);

        if ($validated['action'] === 'choose') {
            // Choisir un site existant comme favori
            $user->favorite_site_id = $validated['site_id'];
        } else {
            // Créer un nouveau site et le définir comme favori
            $site = Site::create([
                'nom' => $validated['site_nom'],
                'adresse' => $validated['site_adresse'],
                'user_id' => $user->id,
            ]);

            $user->favorite_site_id = $site->id;

            // Si l'utilisateur crée un site, il devient propriétaire
            if ($user->role === 'locataire') {
                $user->role = 'les deux';
            } elseif (!$user->role || $user->role === '') {
                $user->role = 'proprietaire';
            }
        }

        $user->onboarded = true;
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', 'Bienvenue ! Votre site favori a été configuré.');
    }
}
