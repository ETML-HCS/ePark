<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
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
            'create_new_site' => 'nullable|boolean',
            'site_id' => 'required_without:create_new_site|nullable|exists:sites,id',
            'site_nom' => 'nullable|required_if:create_new_site,1|string|max:255',
            'site_adresse' => 'nullable|required_if:create_new_site,1|string|max:255',
            'nom' => 'required|string|max:255',
            'caracteristiques' => 'nullable|string|max:1000',
            'hourly_price' => 'required|numeric|min:0',
            'cancel_deadline_hours' => 'required|integer|in:12,24',
        ]);

        /** @var User $user */
        $user = $request->user();

        $siteId = $validated['site_id'] ?? null;
        if (!empty($validated['create_new_site'])) {
            $site = \App\Models\Site::create([
                'nom' => $validated['site_nom'],
                'adresse' => $validated['site_adresse'],
                'user_id' => $user->id,
            ]);
            $siteId = $site->id;
        }

        Place::create([
            'user_id' => $user->id,
            'site_id' => $siteId,
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
            'is_active' => true,
            'cancel_deadline_hours' => (int) $validated['cancel_deadline_hours'],
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
            'cancel_deadline_hours' => 'required|integer|in:12,24',
        ]);

        $place->update([
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
            'cancel_deadline_hours' => (int) $validated['cancel_deadline_hours'],
        ]);

        return redirect()->route('places.mes')
            ->with('success', 'Place mise a jour avec succes !');
    }

    /**
     * Supprime (soft delete) une place si aucune reservation a venir.
     */
    public function destroy(Request $request, Place $place): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($place->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $hasUpcoming = Reservation::where('place_id', $place->id)
            ->where('statut', '!=', 'annulée')
            ->where('date_fin', '>=', now())
            ->exists();

        if ($hasUpcoming) {
            return back()->withErrors([
                'place' => 'Impossible de supprimer : des reservations sont encore actives ou a venir.',
            ]);
        }

        $place->delete();

        return redirect()->route('places.mes')
            ->with('success', 'Place supprimee avec succes.');
    }
}
