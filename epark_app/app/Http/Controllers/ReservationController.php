<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use App\Services\PlaceBlockedSlotsService;
use App\Services\ReservationService;
use App\Support\BookingPeriod;
use App\Support\CsvExporter;
use App\Support\ReservationIcsGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contrôleur de réservations refactorisé.
 * Utilise les services et policies pour une meilleure séparation des responsabilités.
 */
class ReservationController extends Controller
{
    public function __construct(
        private PlaceBlockedSlotsService $availabilityService,
        private ReservationService $reservationService
    ) {}

    /**
     * Liste des réservations de l'utilisateur.
     */
    public function index(Request $request): View
    {
        [$query, $period] = $this->buildFilteredQuery($request);

        $reservations = $query
            ->orderBy($period === 'upcoming' ? 'date_debut' : 'created_at', $period === 'upcoming' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('reservations.index', compact('reservations'));
    }

    /**
     * Exporte la liste filtrée des réservations de l'utilisateur en CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        [$query, $period] = $this->buildFilteredQuery($request);

        $reservations = $query
            ->orderBy($period === 'upcoming' ? 'date_debut' : 'created_at', $period === 'upcoming' ? 'asc' : 'desc')
            ->get();

        $rows = [];
        foreach ($reservations as $reservation) {
            /** @var Reservation $reservation */
            $place = $reservation->place;
            $site = $place?->site;
            $tenant = $reservation->user;
            $rows[] = [
                $reservation->id,
                $reservation->date_debut->format('d.m.Y H:i'),
                $reservation->date_fin->format('d.m.Y H:i'),
                $site !== null ? $site->nom : '',
                $place !== null ? $place->nom : 'Place archivée',
                $tenant !== null ? $tenant->name : '',
                $reservation->vehicle_plate ?? '',
                $reservation->statut,
                $reservation->payment_status,
                $reservation->payment_received_at ? 'Oui' : 'Non',
                number_format(($reservation->amount_cents ?? 0) / 100, 2, '.', ''),
                number_format(($reservation->penalty_cents ?? 0) / 100, 2, '.', ''),
            ];
        }

        return CsvExporter::download(
            'epark-reservations-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Début', 'Fin', 'Site', 'Place', 'Locataire', 'Plaque', 'Statut', 'Paiement', 'Réception confirmée', 'Montant (CHF)', 'Pénalité (CHF)'],
            $rows
        );
    }

    /**
     * @return array{0: Builder<Reservation>, 1: string}
     */
    private function buildFilteredQuery(Request $request): array
    {
        $request->validate([
            'period' => 'nullable|in:upcoming,past,all',
            'status' => 'nullable|in:en_attente,confirmée,annulée,terminee',
            'payment' => 'nullable|in:pending,paid,refunded,failed',
        ]);
        /** @var User $user */
        $user = $request->user();

        $query = Reservation::with(['user', 'place.site']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('place', fn ($q) => $q->where('user_id', $user->id));
            });
        }

        $period = (string) $request->get('period', in_array($request->get('status'), ['annulée', 'terminee'], true) ? 'all' : 'upcoming');
        $status = (string) $request->get('status', '');
        $payment = (string) $request->get('payment', '');

        if ($period === 'past') {
            $query->where('date_fin', '<', now());
        } elseif ($period !== 'all') {
            $query->where('date_fin', '>=', now())
                ->where('statut', '!=', 'annulée');
        }

        if ($status !== '') {
            if ($status === 'terminee') {
                $query->where('statut', 'confirmée')
                    ->where('date_fin', '<', now());
            } else {
                $query->where('statut', $status);
            }
        }

        if ($payment !== '') {
            $query->where('payment_status', $payment);
        }

        return [$query, $period];
    }

    /**
     * Formulaire de création de réservation avec UX optimisée.
     * Pré-sélectionne le site favori de l'utilisateur.
     */
    public function create(Request $request): View
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        /** @var User $user */
        $user = $request->user();

        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        if ($request->has('date')) {
            $selectedDate = Carbon::parse($request->get('date'))->startOfDay();
            if ($selectedDate->lt($today)) {
                $selectedDate = $today->copy();
            }
            if ($selectedDate->gt($maxDate)) {
                $selectedDate = $maxDate->copy()->startOfDay();
            }
        } else {
            $selectedDate = now()->hour >= 18 ? $tomorrow->copy() : $today->copy();
        }

        $savedGroupEntries = $user->secretGroupEntries();
        $savedGroupCodes = $user->normalizedSecretGroupCodes();

        $effectiveGroupCodes = collect($savedGroupCodes)
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '')
            ->unique()
            ->values()
            ->all();

        $result = $this->availabilityService->getAvailablePlacesForDate($selectedDate, $user->id, $effectiveGroupCodes, $user->verifiedEmail());
        $places = $result['places']
            ->where('user_id', '!=', $user->id)
            ->values();
        $placeIds = $places->pluck('id')->all();
        $placeHours = collect($result['placeHours'])
            ->only($placeIds)
            ->toArray();
        $placeSegments = collect($result['placeSegments'])
            ->only($placeIds)
            ->toArray();

        $sites = $places->pluck('site')->filter()->unique('id')->values();

        // 🎯 UX Optimisation: Pré-sélectionner le site favori
        $selectedSiteId = $request->get('site_id') ?? $user->favorite_site_id;
        $selectedPlaceId = $request->get('place_id');

        if ($selectedPlaceId && ! $places->contains('id', (int) $selectedPlaceId)) {
            $selectedPlaceId = null;
        }

        // Si un site est pré-sélectionné mais pas de place, sélectionner la première place disponible
        if ($selectedSiteId && ! $selectedPlaceId) {
            $firstPlace = $places->first(fn ($p) => $p->site_id == $selectedSiteId);
            $selectedPlaceId = $firstPlace?->id;
        }

        return view('reservations.create', [
            'places' => $places,
            'hours' => array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)),
            'placeHours' => $placeHours,
            'placeSegments' => $placeSegments,
            'segments' => BookingPeriod::SEGMENTS,
            'minDate' => $today->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'selectedDate' => $selectedDate->toDateString(),
            'selectedPlaceId' => $selectedPlaceId,
            'selectedSiteId' => $selectedSiteId,
            'sites' => $sites,
            'savedGroupEntries' => $savedGroupEntries,
        ]);
    }

    /**
     * Création d'une réservation.
     */
    public function store(Request $request): RedirectResponse
    {
        $minDate = now()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        $validated = $request->validate([
            'place_id' => 'required|exists:places,id',
            'date' => ['required', 'date', 'after_or_equal:'.$minDate->toDateString(), 'before_or_equal:'.$maxDate->toDateString()],
            'segment' => BookingPeriod::validationRule(),
            'battement' => 'nullable|integer|in:5',
            'vehicle_plate' => 'nullable|string|max:20',
        ]);

        /** @var User $user */
        $user = $request->user();
        $place = Place::findOrFail((int) $validated['place_id']);
        $selectedDate = Carbon::parse($validated['date'])->startOfDay();

        if ($place->user_id === $user->id) {
            return back()->withInput()->withErrors([
                'place_id' => 'Vous ne pouvez pas réserver votre propre place.',
            ]);
        }

        $effectiveGroupCodes = collect($user->normalizedSecretGroupCodes())
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '')
            ->unique()
            ->values()
            ->all();

        if (! $place->isVisibleWithAnyGroupCodes($effectiveGroupCodes, $user->verifiedEmail())) {
            return back()->withInput()->withErrors([
                'place_id' => 'Cette place est réservée à un groupe privé. Code groupe invalide.',
            ]);
        }

        // No payment provider is configured: never allow self-declaring payment on booking creation.
        $isFree = ($place->hourly_price_cents ?? 0) === 0;
        $plate = trim((string) ($validated['vehicle_plate'] ?? ''));
        if ($plate !== '') {
            $normalizedPlate = mb_strtoupper($plate);
            if ($user->quick_vehicle_plate !== $normalizedPlate) {
                $user->quick_vehicle_plate = $normalizedPlate;
                $user->save();
            }
        }

        try {
            $this->reservationService->createReservation(
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

            return redirect()->route('reservations.index')
                ->with('success', 'Réservation effectuée avec succès');

        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['place_id' => $e->getMessage()]);
        }
    }

    /**
     * Affiche une réservation.
     */
    public function show(Request $request, Reservation $reservation): View
    {
        $this->authorize('view', $reservation);

        $reservation->load(['user', 'place.site', 'place.user', 'feedback']);

        return view('reservations.show', compact('reservation'));
    }

    /**
     * Télécharge l'événement de réservation au format iCalendar (.ics).
     */
    public function downloadIcs(Request $request, Reservation $reservation): Response
    {
        $this->authorize('view', $reservation);

        $ics = ReservationIcsGenerator::generate($reservation);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="epark-reservation-'.$reservation->id.'.ics"',
        ]);
    }

    /**
     * Formulaire de modification de reservation (locataire).
     */
    public function edit(Request $request, Reservation $reservation): View
    {
        $this->authorize('update', $reservation);

        $reservation->load('place');

        $today = now()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        $selectedDate = $reservation->date_debut->copy()->startOfDay();
        if ($selectedDate->lt($today)) {
            $selectedDate = $today->copy();
        }
        if ($selectedDate->gt($maxDate)) {
            $selectedDate = $maxDate->copy()->startOfDay();
        }

        $segment = BookingPeriod::detectSegment($reservation->date_debut, $reservation->date_fin);

        return view('reservations.edit', [
            'reservation' => $reservation,
            'place' => $reservation->place,
            'minDate' => $today->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'selectedDate' => $selectedDate->toDateString(),
            'selectedSegment' => $segment,
            'segments' => BookingPeriod::SEGMENTS,
        ]);
    }

    /**
     * Met a jour une reservation en attente.
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('update', $reservation);

        $minDate = now()->startOfDay();
        $maxDate = now()->addWeeks(3)->endOfDay();

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:'.$minDate->toDateString(), 'before_or_equal:'.$maxDate->toDateString()],
            'segment' => BookingPeriod::validationRule(),
        ]);

        $selectedDate = Carbon::parse($validated['date'])->startOfDay();

        try {
            $this->reservationService->rescheduleReservation(
                $reservation,
                $selectedDate,
                $validated['segment']
            );

            return redirect()->route('reservations.show', $reservation)
                ->with('success', 'Reservation modifiee avec succes.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['date' => $e->getMessage()]);
        }
    }

    /**
     * Payer une réservation.
     */
    public function payer(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('pay', $reservation);

        $reservation->load(['user', 'payment']);

        if ($reservation->payment_status === 'paid') {
            return back()->with('success', 'Paiement déjà effectué.');
        }

        try {
            $this->reservationService->processPayment($reservation);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['payment_status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Paiement manuel déclaré. En attente de confirmation du propriétaire.');
    }

    /**
     * Valide une réservation (propriétaire).
     */
    public function valider(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('validate', $reservation);

        $validated = $request->validate([
            'owner_message' => 'nullable|string|max:500',
        ]);

        $reservation->load(['place.site', 'user']);

        try {
            $this->reservationService->confirmReservation(
                $reservation,
                $validated['owner_message'] ?? null
            );

            return redirect()->route('dashboard')->with('success', 'Réservation confirmée.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['payment_status' => $e->getMessage()]);
        }
    }

    /**
     * Refuse une réservation (propriétaire).
     */
    public function refuser(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('refuse', $reservation);

        $validated = $request->validate([
            'owner_message' => 'nullable|string|max:500',
        ]);

        $reservation->load(['place.site', 'user', 'payment']);

        $this->reservationService->cancelReservation(
            $reservation,
            $validated['owner_message'] ?? null
        );

        return redirect()->route('dashboard')->with('success', 'Réservation refusée.');
    }

    /**
     * Confirme la réception effective du règlement par le propriétaire.
     */
    public function confirmPaymentReceived(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('validate', $reservation);

        $reservation->load(['user', 'payment', 'place.site']);

        try {
            $this->reservationService->confirmPaymentReceived($reservation);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['payment_status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Réception du règlement confirmée.');
    }

    /**
     * Enregistre une fin réelle tardive (dépassement) par le propriétaire.
     */
    public function registerOverstay(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('validate', $reservation);

        if ($reservation->statut !== 'confirmée' || $reservation->date_debut->isFuture()) {
            return back()->withErrors([
                'actual_end_at' => 'Un dépassement ne peut être enregistré que sur une réservation confirmée déjà entamée.',
            ]);
        }

        $validated = $request->validate([
            'actual_end_at' => ['required', 'date', 'after:'.$reservation->date_fin->toDateTimeString()],
        ], [
            'actual_end_at.after' => 'L’heure de départ constatée doit être postérieure à la fin prévue.',
        ]);

        $actualEnd = Carbon::parse($validated['actual_end_at']);
        $this->reservationService->registerActualEnd($reservation, $actualEnd);

        return back()->with('success', 'Départ tardif enregistré et pénalité recalculée.');
    }

    /**
     * Annule une réservation (créateur ou admin).
     */
    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        $reservation->load(['place.site', 'user', 'payment']);

        $this->reservationService->cancelReservation($reservation);

        return redirect()->route('reservations.index')
            ->with('success', 'Réservation annulée et place libérée.');
    }
}
