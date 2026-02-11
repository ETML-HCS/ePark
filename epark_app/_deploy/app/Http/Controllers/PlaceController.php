<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlaceController extends Controller
{
    /**
     * Affiche toutes les places disponibles (page d'accueil publique).
     */
    public function index(): View
    {
        $places = Place::where('is_active', true)
            ->with(['user:id,name', 'site:id,nom,adresse'])
            ->get();

        return view('places.index', compact('places'));
    }

    /**
     * Formulaire de création d'une place.
     */
    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $sites = $user->sites()->orderBy('nom')->get();

        // Pré-sélectionner le site favori
        $selectedSiteId = $user->favorite_site_id;

        return view('places.create', compact('sites', 'selectedSiteId'));
    }

    /**
     * Enregistre une nouvelle place.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'nom' => 'required|string|max:255',
            'caracteristiques' => 'nullable|string|max:1000',
            'hourly_price' => 'required|numeric|min:0',
        ]);

        /** @var User $user */
        $user = $request->user();

        Place::create([
            'user_id' => $user->id,
            'site_id' => $validated['site_id'],
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
            'is_active' => true,
        ]);

        return redirect()->route('places.mes')
            ->with('success', 'Place ajoutée avec succès !');
    }

    /**
     * Affiche les places proposées par l'utilisateur connecté.
     */
    public function mesPlaces(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $places = $user->places()
            ->with('site:id,nom,adresse')
            ->get();

        return view('places.mes', compact('places'));
    }

    /**
     * Formulaire d'edition d'une place.
     */
    public function edit(Request $request, Place $place): View
    {
        /** @var User $user */
        $user = $request->user();

        if ($place->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return view('places.edit', compact('place'));
    }

    /**
     * Met a jour une place existante.
     */
    public function update(Request $request, Place $place): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($place->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'caracteristiques' => 'nullable|string|max:1000',
            'hourly_price' => 'required|numeric|min:0',
        ]);

        $place->update([
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
        ]);

        return redirect()->route('places.mes')
            ->with('success', 'Place mise a jour avec succes !');
    }
}
