<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use App\Services\PlaceAvailabilityService;
use App\Services\ReservationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Contrôleur de réservations refactorisé.
 * Utilise les services et policies pour une meilleure séparation des responsabilités.
 */
class ReservationController extends Controller
{
    public function __construct(
        private PlaceAvailabilityService $availabilityService,
        private ReservationService $reservationService
    ) {}

    /**
     * Liste des réservations de l'utilisateur.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $query = Reservation::with(['user', 'place.site']);

        // Les admins voient toutes les réservations
        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                // Réservations créées par l'utilisateur
                $q->where('user_id', $user->id)
                    // Ou réservations sur les places de l'utilisateur
                    ->orWhereHas('place', fn($q) => $q->where('user_id', $user->id));
            });
        }

        $period = $request->get('period', 'upcoming');
        $status = $request->get('status', '');
        $payment = $request->get('payment', '');
        $today = now()->startOfDay();

        if ($period === 'past') {
            $query->whereDate('date_fin', '<', $today);
        } elseif ($period === 'all') {
            // Pas de filtre
        } else {
            // Par défaut : réservations à venir (y compris aujourd'hui)
            $query->whereDate('date_fin', '>=', $today)
                ->where('statut', '!=', 'annulée');
        }

        if (!empty($status)) {
            if ($status === 'terminee') {
                $query->where('statut', 'confirmée')
                    ->whereDate('date_fin', '<', $today);
            } else {
                $query->where('statut', $status);
            }
        }

        if (!empty($payment)) {
            $query->where('payment_status', $payment);
        }

        $reservations = $query->orderByDesc('created_at')->get();

        return view('reservations.index', compact('reservations'));
    }

    /**
     * Formulaire de création de réservation avec UX optimisée.
     * Pré-sélectionne le site favori de l'utilisateur.
     */
    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $today = now()->startOfDay();
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
            $selectedDate = $today->copy();
        }

        $result = $this->availabilityService->getAvailablePlacesForDate($selectedDate, $user->id);
        $places = $result['places'];
        $placeHours = $result['placeHours'];

        $sites = $places->pluck('site')->filter()->unique('id')->values();

        // 🎯 UX Optimisation: Pré-sélectionner le site favori
        $selectedSiteId = $request->get('site_id') ?? $user->favorite_site_id;
        $selectedPlaceId = $request->get('place_id');

        if ($selectedPlaceId && !$places->contains('id', (int) $selectedPlaceId)) {
            $selectedPlaceId = null;
        }

        // Si un site est pré-sélectionné mais pas de place, sélectionner la première place disponible
        if ($selectedSiteId && !$selectedPlaceId) {
            $firstPlace = $places->first(fn($p) => $p->site_id == $selectedSiteId);
            $selectedPlaceId = $firstPlace?->id;
        }

        return view('reservations.create', [
            'places' => $places,
            'hours' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
            'placeHours' => $placeHours,
            'minDate' => $today->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'selectedDate' => $selectedDate->toDateString(),
            'selectedPlaceId' => $selectedPlaceId,
            'selectedSiteId' => $selectedSiteId,
            'sites' => $sites,
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
            'date' => ['required', 'date', 'after_or_equal:' . $minDate->toDateString(), 'before_or_equal:' . $maxDate->toDateString()],
            'segment' => 'required|in:matin_travail,aprem_travail,soir,nuit',
            'battement' => 'nullable|integer|in:5',
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

        try {
            $this->reservationService->createReservation(
                user: $user,
                place: $place,
                date: $selectedDate,
                segment: $validated['segment'],
                battement: (int) ($validated['battement'] ?? 5),
                paid: $request->boolean('paiement_effectue')
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

        $reservation->load(['user', 'place.site']);

        return view('reservations.show', compact('reservation'));
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

        $startMinutes = ((int) $reservation->date_debut->format('H')) * 60 + (int) $reservation->date_debut->format('i');
        $segment = match (true) {
            $startMinutes >= 480 && $startMinutes < 720 => 'matin_travail',
            $startMinutes >= 720 && $startMinutes < 1050 => 'aprem_travail',
            $startMinutes >= 1080 && $startMinutes < 1440 => 'soir',
            default => 'nuit',
        };

        return view('reservations.edit', [
            'reservation' => $reservation,
            'place' => $reservation->place,
            'minDate' => $today->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'selectedDate' => $selectedDate->toDateString(),
            'selectedSegment' => $segment,
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
            'date' => ['required', 'date', 'after_or_equal:' . $minDate->toDateString(), 'before_or_equal:' . $maxDate->toDateString()],
            'segment' => 'required|in:matin_travail,aprem_travail,soir,nuit',
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

        $this->reservationService->processPayment($reservation);

        return back()->with('success', 'Paiement effectué. En attente de confirmation propriétaire.');
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
