<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour le feedback des réservations.
 * 
 * Permet aux utilisateurs de laisser un avis après une réservation terminée.
 */
class FeedbackController extends Controller
{
    /**
     * Affiche le formulaire de feedback pour une réservation.
     */
    public function create(Request $request, Reservation $reservation): View
    {
        /** @var User $user */
        $user = $request->user();

        // Vérifier que l'utilisateur est le créateur de la réservation
        if ($user->id !== $reservation->user_id) {
            abort(403, 'Non autorisé.');
        }

        // Vérifier que la réservation est terminée (confirmée et passée)
        if ($reservation->statut !== 'confirmée' || $reservation->date_fin > now()) {
            abort(403, 'Vous ne pouvez laisser un avis que pour une réservation terminée.');
        }

        // Vérifier qu'il n'y a pas déjà un feedback
        if ($reservation->feedback()->exists()) {
            return redirect()->route('reservations.show', $reservation)
                ->with('info', 'Vous avez déjà laissé un avis pour cette réservation.');
        }

        $reservation->load(['place.site']);

        return view('feedbacks.create', compact('reservation'));
    }

    /**
     * Enregistre le feedback.
     */
    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Vérifications
        if ($user->id !== $reservation->user_id) {
            abort(403, 'Non autorisé.');
        }

        if ($reservation->statut !== 'confirmée' || $reservation->date_fin > now()) {
            abort(403, 'Vous ne pouvez laisser un avis que pour une réservation terminée.');
        }

        if ($reservation->feedback()->exists()) {
            return redirect()->route('reservations.show', $reservation)
                ->with('info', 'Vous avez déjà laissé un avis pour cette réservation.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Feedback::create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'Merci pour votre avis !');
    }
}
