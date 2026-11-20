<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PlaceController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Affiche toutes les places disponibles (page d'accueil publique).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $request->validate(['key' => 'nullable|string|max:64']);
        // Si clé fournie dans l'URL ou mémorisée en cookie, auto-connexion et redirection Express directe
        $keyParam = $request->query('key');
        if (! empty($keyParam)) {
            $userByKey = User::findByExpressToken($keyParam);
            if ($userByKey) {
                Auth::login($userByKey, remember: true);
                $request->session()->regenerate();
                Cookie::queue('epark_express_key', $userByKey->express_token, 60 * 24 * 365);

                return redirect()->route('express.index');
            }
        }

        if (! Auth::check()) {
            $cookieKey = $request->cookie('epark_express_key');
            if (is_string($cookieKey) && $cookieKey !== '') {
                $userByCookie = User::findByExpressToken($cookieKey);
                if ($userByCookie) {
                    Auth::login($userByCookie, remember: true);
                    $request->session()->regenerate();

                    return redirect()->route('express.index');
                }
            }
        }

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
            ->with(['user:id,name', 'site:id,nom,adresse,latitude,longitude', 'blockedSlots:place_id,day_of_week,start_time,end_time'])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->get()
            ->filter(fn (Place $place) => $place->isVisibleWithAnyGroupCodes($effectiveGroupCodes, $request->user()?->verifiedEmail()))
            ->values();

        $mapSites = $places
            ->filter(fn (Place $place) => $place->site?->hasCoordinates() === true)
            ->groupBy('site_id')
            ->map(function ($sitePlaces) {
                /** @var Place $first */
                $first = $sitePlaces->first();
                /** @var Site $site */
                $site = $first->site;
                $minCents = (int) $sitePlaces->min('hourly_price_cents');

                return [
                    'id' => $site->id,
                    'nom' => $site->nom,
                    'adresse' => $site->adresse,
                    'latitude' => $site->latitude,
                    'longitude' => $site->longitude,
                    'places_count' => $sitePlaces->count(),
                    'min_price_label' => format_chf($minCents / 100).'/h',
                    'url' => route('express.index', ['site_id' => $site->id]),
                ];
            })
            ->values()
            ->all();

        return view('places.index', compact('places', 'groupCode', 'savedGroupEntries', 'mapSites'));
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
        /** @var User $user */
        $user = $request->user();

        $siteNomRules = ['nullable', 'required_if:create_new_site,1', 'string', 'max:255'];
        if ($request->boolean('create_new_site')) {
            $siteNomRules[] = Rule::unique('sites', 'nom')->where('user_id', $user->id);
        }

        $validated = $request->validate([
            'create_new_site' => 'nullable|boolean',
            'site_id' => 'required_unless:create_new_site,1,true|nullable|exists:sites,id',
            'site_nom' => $siteNomRules,
            'site_adresse' => 'nullable|required_if:create_new_site,1|string|max:255',
            'nom' => 'required|string|max:255',
            'caracteristiques' => 'nullable|string|max:1000',
            'hourly_price' => 'required|numeric|min:0',
            'cancel_deadline_hours' => 'nullable|integer|in:12,24',
            'is_group_reserved' => 'nullable|boolean',
            'group_source' => 'nullable|in:existing,manual',
            'secret_group_index' => 'nullable|integer|min:0',
            'group_name' => 'nullable|string|max:120',
            'group_access_code' => 'nullable|string|min:4|max:32',
            'group_allowed_email_domains_raw' => 'nullable|string|max:1000',
        ], [
            'site_nom.unique' => 'Vous avez déjà un site portant ce nom.',
        ]);

        $siteId = $validated['site_id'] ?? null;
        if (! empty($validated['create_new_site'])) {
            $site = Site::create([
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
            $groupSource = $validated['group_source'] ?? (! empty($savedGroupEntries) ? 'existing' : 'manual');

            if ($groupSource === 'existing') {
                $selectedIndex = (int) ($validated['secret_group_index'] ?? -1);
                $selectedEntry = $savedGroupEntries[$selectedIndex] ?? null;

                if (! $selectedEntry || empty($selectedEntry['code'])) {
                    return back()->withInput()->withErrors([
                        'secret_group_index' => 'Sélectionnez un groupe secret existant valide.',
                    ]);
                }

                $groupName = $selectedEntry['name'] !== '' ? $selectedEntry['name'] : 'Groupe privé';
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

        $place = Place::create([
            'user_id' => $user->id,
            'site_id' => $siteId,
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
            'is_active' => true,
            'cancel_deadline_hours' => (int) ($validated['cancel_deadline_hours'] ?? 24),
            'is_group_reserved' => $isGroupReserved,
            'group_name' => $groupName,
            'group_access_code_hash' => $groupCode ? Hash::make($groupCode) : null,
            'group_allowed_email_domains' => $isGroupReserved ? $groupAllowedEmailDomains : [],
        ]);

        $this->auditLogger->log('create', 'place', $place->id, null, [
            'nom' => $place->nom,
            'site_id' => $place->site_id,
            'hourly_price_cents' => $place->hourly_price_cents,
        ], $user->id);
        Cache::put('admin_stats_version', ((int) Cache::get('admin_stats_version', 1)) + 1);

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

            if (! $updated) {
                $entries->push([
                    'name' => $groupName,
                    'code' => $groupCode,
                ]);
            }

            $user->secret_group_codes = $entries->values()->all();
            $user->save();
        }

        if ($user->role === 'locataire') {
            $user->update(['role' => 'les deux']);
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
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
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
            'cancel_deadline_hours' => 'nullable|integer|in:12,24',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = [
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'hourly_price_cents' => (int) round($validated['hourly_price'] * 100),
            'cancel_deadline_hours' => (int) ($validated['cancel_deadline_hours'] ?? $place->cancel_deadline_hours ?? 24),
        ];

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $before = [
            'nom' => $place->nom,
            'hourly_price_cents' => $place->hourly_price_cents,
            'is_active' => $place->is_active,
        ];

        $place->update($updateData);

        $this->auditLogger->log('update', 'place', $place->id, $before, [
            'nom' => $place->nom,
            'hourly_price_cents' => $place->hourly_price_cents,
            'is_active' => $place->is_active,
        ], $user->id);
        Cache::put('admin_stats_version', ((int) Cache::get('admin_stats_version', 1)) + 1);

        return redirect()->route('places.mes')
            ->with('success', 'Place mise à jour avec succès !');
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

        $before = ['nom' => $place->nom, 'site_id' => $place->site_id];
        $place->delete();

        $this->auditLogger->log('delete', 'place', $place->id, $before, null, $user->id);
        Cache::put('admin_stats_version', ((int) Cache::get('admin_stats_version', 1)) + 1);

        return redirect()->route('places.mes')
            ->with('success', 'Place supprimee avec succes.');
    }
}
