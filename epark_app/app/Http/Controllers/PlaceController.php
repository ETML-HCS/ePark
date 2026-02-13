<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class PlaceController extends Controller
{
    /**
     * Affiche toutes les places disponibles (page d'accueil publique).
     */
    public function index(Request $request): View
    {
        $savedGroupEntries = $request->user()?->secretGroupEntries() ?? [];
        $queryGroupCode = trim((string) $request->query('group_code', ''));
        $savedGroupCodes = $request->user()?->normalizedSecretGroupCodes() ?? [];

        $effectiveGroupCodes = collect([$queryGroupCode])
            ->merge($savedGroupCodes)
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '')
            ->unique()
            ->values()
            ->all();

        $groupCode = $queryGroupCode !== ''
            ? $queryGroupCode
            : ($savedGroupCodes[0] ?? '');

        $places = Place::where('is_active', true)
            ->with(['user:id,name', 'site:id,nom,adresse', 'blockedSlots:place_id,day_of_week,start_time,end_time'])
            ->get()
            ->filter(fn (Place $place) => $place->isVisibleWithAnyGroupCodes($effectiveGroupCodes, $request->user()?->email))
            ->values();

        return view('places.index', compact('places', 'groupCode', 'savedGroupEntries'));
    }

    /**
     * Formulaire de création d'une place.
     */
    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $sites = $user->sites()->orderBy('nom')->get();
        $savedGroupEntries = $user->secretGroupEntries();

        // Pré-sélectionner le site favori
        $selectedSiteId = $user->favorite_site_id;

        return view('places.create', compact('sites', 'selectedSiteId', 'savedGroupEntries'));
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
            'is_group_reserved' => 'nullable|boolean',
            'group_source' => 'nullable|in:existing,manual',
            'secret_group_index' => 'nullable|integer|min:0',
            'group_name' => 'nullable|string|max:120',
            'group_access_code' => 'nullable|string|min:4|max:32',
            'group_allowed_email_domains_raw' => 'nullable|string|max:1000',
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

        $isGroupReserved = $request->boolean('is_group_reserved');
        $groupName = null;
        $groupCode = null;
        $groupAllowedEmailDomains = [];

        $groupAllowedEmailDomains = collect(preg_split('/[\r\n,;]+/', (string) ($validated['group_allowed_email_domains_raw'] ?? '')) ?: [])
            ->map(fn ($domain) => ltrim(mb_strtolower(trim((string) $domain)), '@'))
            ->filter(fn ($domain) => $domain !== '')
            ->unique()
            ->values()
            ->all();

        if ($isGroupReserved) {
            $savedGroupEntries = $user->secretGroupEntries();
            $groupSource = $validated['group_source'] ?? (!empty($savedGroupEntries) ? 'existing' : 'manual');

            if ($groupSource === 'existing') {
                $selectedIndex = (int) ($validated['secret_group_index'] ?? -1);
                $selectedEntry = $savedGroupEntries[$selectedIndex] ?? null;

                if (!$selectedEntry || empty($selectedEntry['code'])) {
                    return back()->withInput()->withErrors([
                        'secret_group_index' => 'Sélectionnez un groupe secret existant valide.',
                    ]);
                }

                $groupName = (string) ($selectedEntry['name'] ?? 'Groupe privé');
                $groupCode = (string) $selectedEntry['code'];
            } else {
                $groupName = trim((string) ($validated['group_name'] ?? ''));
                $groupCode = trim((string) ($validated['group_access_code'] ?? ''));

                if ($groupName === '' || $groupCode === '') {
                    return back()->withInput()->withErrors([
                        'group_name' => 'Le nom du groupe et le code sont requis pour une place de groupe.',
                    ]);
                }
            }
        }

        Place::create([
            'user_id' => $user->id,
            'site_id' => $siteId,
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
            'is_active' => true,
            'cancel_deadline_hours' => (int) $validated['cancel_deadline_hours'],
            'is_group_reserved' => $isGroupReserved,
            'group_name' => $groupName,
            'group_access_code_hash' => $groupCode ? Hash::make($groupCode) : null,
            'group_allowed_email_domains' => $isGroupReserved ? $groupAllowedEmailDomains : [],
        ]);

        if ($isGroupReserved && $groupName && $groupCode) {
            $entries = collect($user->secretGroupEntries());
            $updated = false;

            $entries = $entries->map(function (array $entry) use ($groupName, $groupCode, &$updated) {
                if (mb_strtolower((string) $entry['code']) === mb_strtolower($groupCode)
                    || mb_strtolower((string) $entry['name']) === mb_strtolower($groupName)) {
                    $updated = true;
                    return [
                        'name' => $groupName,
                        'code' => $groupCode,
                    ];
                }

                return $entry;
            });

            if (!$updated) {
                $entries->push([
                    'name' => $groupName,
                    'code' => $groupCode,
                ]);
            }

            $user->secret_group_codes = $entries->values()->all();
            $user->save();
        }

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
            ->with(['site:id,nom,adresse', 'blockedSlots:place_id,day_of_week,start_time,end_time'])
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
