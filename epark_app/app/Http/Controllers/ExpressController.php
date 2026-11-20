<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use App\Services\PlaceBlockedSlotsService;
use App\Services\ReservationService;
use App\Support\BookingPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ExpressController extends Controller
{
    public function __construct(
        private PlaceBlockedSlotsService $availabilityService,
        private ReservationService $reservationService
    ) {}

    /**
     * Page principale Express / Version Rapide.
     * Gère la reconnaissance automatique par clé (URL ou cookie).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $request->validate(['key' => 'nullable|string|max:64', 'date' => 'nullable|date_format:Y-m-d', 'site_id' => 'nullable|integer', 'place_id' => 'nullable|integer', 'segment' => 'nullable|in:'.implode(',', array_keys(BookingPeriod::SEGMENTS))]);
        $keyParam = $request->query('key');

        // Si une clé est fournie dans l'URL, tenter la connexion immédiate
        if (! empty($keyParam)) {
            $userByKey = User::findByExpressToken($keyParam);
            if ($userByKey) {
                Auth::login($userByKey, remember: true);
                $request->session()->regenerate();
                Cookie::queue('epark_express_key', $userByKey->express_token, 60 * 24 * 365);

                return redirect()->route('express.index');
            }
        }

        // Si non connecté, vérifier le cookie persistant
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

        // Si toujours non connecté, afficher l'écran d'accès rapide par clé
        if (! Auth::check()) {
            $request->session()->put('url.intended', route('express.index', $request->only(['date', 'segment', 'site_id', 'place_id'])));

            return view('express.login');
        }

        /** @var User $user */
        $user = Auth::user();

        // Si l'utilisateur n'est pas encore onboarded, le valider directement en mode express
        if (! $user->onboarded) {
            $user->onboarded = true;
            $user->save();
        }

        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        $selectedDateString = $request->get('date') ?: (now()->hour >= 18 ? $tomorrow : $today)->toDateString();
        $selectedDate = Carbon::parse($selectedDateString)->startOfDay();
        if ($selectedDate->lt($today)) {
            $selectedDate = $today->copy();
        }
        if ($selectedDate->gt($maxDate)) {
            $selectedDate = $maxDate->copy()->startOfDay();
        }

        $savedGroupCodes = $user->normalizedSecretGroupCodes();
        $result = $this->availabilityService->getAvailablePlacesForDate(
            $selectedDate,
            $user->id,
            $savedGroupCodes,
            $user->verifiedEmail()
        );

        $places = $result['places']
            ->where('user_id', '!=', $user->id)
            ->values();

        $placeHours = collect($result['placeHours'])
            ->only($places->pluck('id')->all())
            ->toArray();

        // Récupérer le site favori
        $favoriteSiteId = $user->favorite_site_id;
        $selectedSiteId = $request->get('site_id') ?? $favoriteSiteId;

        // Pré-sélection de la place idéale
        $selectedPlaceId = $request->get('place_id');
        if ($selectedPlaceId && ! $places->contains('id', (int) $selectedPlaceId)) {
            $selectedPlaceId = null;
        }

        if (! $selectedPlaceId && $selectedSiteId) {
            $firstInSite = $places->first(fn ($p) => (int) $p->site_id === (int) $selectedSiteId);
            $selectedPlaceId = $firstInSite?->id;
        }

        if (! $selectedPlaceId && $places->isNotEmpty()) {
            $selectedPlaceId = $places->first()->id;
        }

        // Réservations actives récentes de l'utilisateur
        $activeReservations = Reservation::with(['place.site'])
            ->where('user_id', $user->id)
            ->where('statut', '!=', 'annulée')
            ->where('date_fin', '>=', now())
            ->orderBy('date_debut')
            ->take(3)
            ->get();

        $expressToken = $user->getOrGenerateExpressToken();
        $directLink = url('/express?key='.$expressToken);

        $sites = $places->pluck('site')->filter();
        if ($user->favoriteSite) {
            $sites->push($user->favoriteSite);
        }
        $lastReservation = Reservation::query()
            ->where('user_id', $user->id)
            ->active()
            ->latest('created_at')
            ->first();
        $bookingConfig = [
            'date' => $selectedDate->toDateString(),
            'places' => $this->serializePlaces($places, $result['placeSegments'], $selectedDate),
            'sites' => $sites->unique('id')->map(fn ($site) => ['id' => $site->id, 'nom' => $site->nom])->values(),
            'segments' => BookingPeriod::SEGMENTS,
            'selectedSiteId' => (string) $selectedSiteId,
            'selectedPlaceId' => (string) ($request->get('place_id') ?: $lastReservation?->place_id ?: $selectedPlaceId),
            'selectedSegment' => $request->get('segment', ''),
            'vehiclePlate' => (string) ($user->quick_vehicle_plate ?? ''),
            'today' => $today->toDateString(),
            'tomorrow' => $tomorrow->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'availabilityUrl' => route('express.availability'),
            'bookUrl' => route('express.book'),
            'csrf' => csrf_token(),
        ];

        return view('express.index', [
            'user' => $user,
            'bookingConfig' => $bookingConfig,
            'expressToken' => $expressToken,
            'directLink' => $directLink,
            'places' => $places,
            'placeHours' => $placeHours,
            'selectedDate' => $selectedDate->toDateString(),
            'todayDate' => $today->toDateString(),
            'tomorrowDate' => $tomorrow->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'selectedPlaceId' => $selectedPlaceId,
            'selectedSiteId' => $selectedSiteId,
            'activeReservations' => $activeReservations,
            'hasAnyActivePlaces' => Place::where('is_active', true)->exists(),
        ]);
    }

    /**
     * Retourne les disponibilités et places pour une date donnée en JSON (chargement instantané 0-rechargement).
     */
    public function availability(Request $request): JsonResponse
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        /** @var User $user */
        $user = $request->user();

        $today = now()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        $dateStr = $request->get('date') ?: $today->toDateString();
        $date = Carbon::parse($dateStr)->startOfDay();
        if ($date->lt($today)) {
            $date = $today->copy();
        }
        if ($date->gt($maxDate)) {
            $date = $maxDate->copy()->startOfDay();
        }

        $savedGroupCodes = $user->normalizedSecretGroupCodes();
        $result = $this->availabilityService->getAvailablePlacesForDate(
            $date,
            $user->id,
            $savedGroupCodes,
            $user->verifiedEmail()
        );

        $places = $result['places']
            ->where('user_id', '!=', $user->id)
            ->values();

        $placeHours = collect($result['placeHours'])
            ->only($places->pluck('id')->all())
            ->toArray();

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'places' => $this->serializePlaces($places, $result['placeSegments'], $date),
            'placeHours' => $placeHours,
        ]);
    }

    /**
     * @param  Collection<int, Place>  $places
     * @param  array<int, array<int, string>>  $placeSegments
     * @return array<int, array<string, mixed>>
     */
    private function serializePlaces(Collection $places, array $placeSegments, Carbon $date): array
    {
        return $places->map(function (Place $place) use ($placeSegments, $date): array {
            $site = $place->site;

            return [
                'id' => $place->id,
                'nom' => $place->nom,
                'adresse' => $site !== null ? $site->adresse : '',
                'site_id' => $place->site_id,
                'site_nom' => $site !== null ? $site->nom : 'Parking',
                'hourly_price' => $place->minHourlyPriceCents() / 100,
                'rating_avg' => $place->feedbacks_avg_rating !== null ? round((float) $place->feedbacks_avg_rating, 1) : null,
                'rating_count' => (int) ($place->feedbacks_count ?? 0),
                'availableSegments' => $placeSegments[$place->id] ?? [],
                'quotes' => collect(BookingPeriod::SEGMENTS)->map(fn ($period, $segment) => $place->quoteCentsFor($date, $segment))->all(),
            ];
        })->values()->all();
    }

    /**
     * Connexion rapide via la clé abonné (1 champ, sans mot de passe).
     */
    public function loginWithKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'express_key' => 'required|string|min:4|max:64',
            'remember_device' => 'nullable|boolean',
        ]);

        $key = trim($validated['express_key']);
        $user = User::findByExpressToken($key);

        if (! $user) {
            return back()->withInput()->withErrors([
                'express_key' => 'Clé d\'accès invalide ou inconnue. Vérifiez votre clé abonné.',
            ]);
        }

        $remember = $request->boolean('remember_device');
        Auth::login($user, remember: $remember);
        $request->session()->regenerate();

        if ($remember) {
            Cookie::queue('epark_express_key', $user->express_token, 60 * 24 * 365);
        } else {
            Cookie::queue(Cookie::forget('epark_express_key'));
        }

        return redirect()->intended(route('express.index'))
            ->with('success', 'Bienvenue '.$user->name.' !');
    }

    /**
     * Réservation ultra-rapide en 1 clic (avec auto-paiement optionnel ou par défaut).
     */
    public function book(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $minDate = now()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        $validated = $request->validate([
            'place_id' => 'required|exists:places,id',
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$minDate->toDateString(), 'before_or_equal:'.$maxDate->toDateString()],
            'segment' => BookingPeriod::validationRule(),
            'battement' => 'nullable|integer|in:5',
            'auto_pay' => 'nullable|boolean',
            'vehicle_plate' => 'nullable|string|max:20',
        ]);

        $place = Place::with('site')->findOrFail((int) $validated['place_id']);
        $selectedDate = Carbon::parse($validated['date'])->startOfDay();

        if ($place->user_id === $user->id) {
            $msg = 'Vous ne pouvez pas réserver votre propre place.';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['place_id' => $msg]);
        }

        $effectiveGroupCodes = collect($user->normalizedSecretGroupCodes())
            ->filter()
            ->all();

        if (! $place->isVisibleWithAnyGroupCodes($effectiveGroupCodes, $user->verifiedEmail())) {
            $msg = 'Cette place est réservée à un groupe privé.';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 403)
                : back()->withErrors(['place_id' => $msg]);
        }

        // No payment provider is configured: never simulate a debit or bypass the owner.
        $useAutoPay = false;
        $isFree = $place->isFreeFor($selectedDate, $validated['segment']);
        $plate = trim((string) ($validated['vehicle_plate'] ?? ''));
        if ($plate !== '') {
            $normalizedPlate = mb_strtoupper($plate);
            if ($user->quick_vehicle_plate !== $normalizedPlate) {
                $user->quick_vehicle_plate = $normalizedPlate;
                $user->save();
            }
        }

        try {
            // Réservation + paiement automatique en 1 clic
            $reservation = $this->reservationService->createReservation(
                user: $user,
                place: $place,
                date: $selectedDate,
                segment: $validated['segment'],
                battement: (int) ($validated['battement'] ?? 5),
                paid: $isFree,
                autoConfirm: false,
                paymentProvider: $isFree ? 'free' : 'manual',
                vehiclePlate: $plate !== '' ? $plate : null
            );

            $segmentLabels = [
                'nuit' => 'Nuit (00:00 - 07:30)',
                'matin_travail' => 'Matin (08:00 - 12:00)',
                'aprem_travail' => 'Après-midi (12:00 - 17:30)',
                'soir' => 'Soir (18:00 - 23:59)',
            ];

            if ($request->wantsJson()) {
                $placeSite = $place->site;
                $formattedStart = $reservation->date_debut->copy();
                $formattedStart->locale('fr');

                return response()->json([
                    'success' => true,
                    'reservation_id' => $reservation->id,
                    'url' => route('reservations.show', $reservation),
                    'place_nom' => $place->nom,
                    'site_nom' => $placeSite !== null ? $placeSite->nom : 'Site',
                    'site_adresse' => $placeSite !== null ? $placeSite->adresse : '',
                    'date_formatted' => $formattedStart->isoFormat('dddd D MMMM YYYY'),
                    'segment_label' => BookingPeriod::SEGMENTS[$validated['segment']]['hours'],
                    'amount_chf' => format_chf($reservation->amount_cents ? $reservation->amount_cents / 100 : 0),
                    'statut' => $reservation->statut,
                    'paid' => $reservation->isPaid(),
                    'message' => 'Votre demande a été transmise au propriétaire. La place sera garantie après sa confirmation.',
                ]);
            }

            return redirect()->route('express.index')->with('success', '⚡ Réservation effectuée avec succès !');

        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['place_id' => $e->getMessage()]);
        }
    }

    /**
     * Régénère la clé d'accès rapide de l'utilisateur.
     */
    public function regenerateKey(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $newKey = $user->regenerateExpressToken();

        // Mettre à jour le cookie
        Cookie::queue('epark_express_key', $newKey, 60 * 24 * 365);

        return back()->with('success', 'Nouvelle Clé Abonné générée avec succès.');
    }

    /**
     * Active/désactive le paiement automatique en 1 clic.
     */
    public function toggleAutoPay(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->boolean('enabled')) {
            return response()->json(['success' => false, 'message' => 'Le paiement en ligne n’est pas disponible.'], 422);
        }
        /** @var User $user */
        $user = $request->user();
        $user->auto_pay_enabled = false;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'auto_pay_enabled' => $user->auto_pay_enabled,
                'message' => $user->auto_pay_enabled
                    ? 'Paiement automatique activé'
                    : 'Paiement automatique désactivé',
            ]);
        }

        return back()->with('success', 'Paramètre de paiement automatique mis à jour.');
    }

    /**
     * Enregistre le cookie sur l'appareil pour mémoriser l'abonné.
     */
    public function rememberDevice(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        Cookie::queue('epark_express_key', $user->getOrGenerateExpressToken(), 60 * 24 * 365);

        return response()->json(['success' => true, 'message' => 'Appareil mémorisé avec succès.']);
    }

    /**
     * Met à jour la plaque d'immatriculation rapide de l'abonné.
     */
    public function updatePlate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate' => 'nullable|string|max:20',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->quick_vehicle_plate = ! empty($validated['plate']) ? strtoupper(trim($validated['plate'])) : null;
        $user->save();

        return response()->json([
            'success' => true,
            'plate' => $user->quick_vehicle_plate,
            'message' => 'Plaque d\'immatriculation enregistrée.',
        ]);
    }

    /**
     * Oublie la clé sur cet appareil (suppression cookie).
     */
    public function forgetDevice(Request $request): RedirectResponse
    {
        Cookie::queue(Cookie::forget('epark_express_key'));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('express.index')->with('success', 'Clé oubliée sur cet appareil.');
    }
}
