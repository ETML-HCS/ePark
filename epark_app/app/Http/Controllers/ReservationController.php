<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function valider($id)
    {
        $reservation = \App\Models\Reservation::with('place')->findOrFail($id);
        // Seul le propriétaire de la place peut valider
        if (auth()->id() !== $reservation->place->user_id) {
            abort(403, 'Non autorisé.');
        }
        $reservation->statut = 'validée';
        $reservation->save();
        $reservation->user->notify(new \App\Notifications\ReservationStatusChanged($reservation, 'validée'));

        return redirect()->route('dashboard')->with('success', 'Réservation validée.');
    }

    public function refuser($id)
    {
        $reservation = \App\Models\Reservation::with('place')->findOrFail($id);
        if (auth()->id() !== $reservation->place->user_id) {
            abort(403, 'Non autorisé.');
        }
        $reservation->statut = 'refusée';
        $reservation->save();
        // Libérer la place
        $place = $reservation->place;
        $place->disponible = true;
        $place->save();
        $reservation->user->notify(new \App\Notifications\ReservationStatusChanged($reservation, 'refusée'));

        return redirect()->route('dashboard')->with('success', 'Réservation refusée et place libérée.');
    }

    public function destroy($id)
    {
        $reservation = \App\Models\Reservation::findOrFail($id);
        // Seul le créateur ou un admin peut annuler
        if (auth()->id() !== $reservation->user_id) {
            abort(403, 'Non autorisé.');
        }
        // Libérer la place
        $place = $reservation->place;
        $place->disponible = true;
        $place->save();
        $reservation->statut = 'annulée';
        $reservation->save();
        $reservation->user->notify(new \App\Notifications\ReservationStatusChanged($reservation, 'annulée'));

        return redirect()->route('reservations.index')->with('success', 'Réservation annulée et place libérée.');
    }

    public function index()
    {
        $reservations = \App\Models\Reservation::with(['user', 'place'])->get();

        return view('reservations.index', compact('reservations'));
    }

    public function create()
    {
        $places = \App\Models\Place::all();
        // créneaux horaires entiers (00:00 à 23:00)
        $hours = array_map(function($h){ return sprintf('%02d:00', $h); }, range(0,23));

        return view('reservations.create', compact('places', 'hours'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'place_id' => 'required|exists:places,id',
            'date' => 'required|date',
            'start_hour' => ['required', 'regex:/^([01]\d|2[0-3]):00$/'],
            'battement' => 'required|integer|in:0,10,15,20',
        ]);

        // Construire les datetimes de début/fin (créneau d'1 heure)
        $start = \Carbon\Carbon::parse($validated['date'].' '.$validated['start_hour']);
        $end = $start->copy()->addHour();

        // Vérifier chevauchement pour la même place en incluant les battements
        if (\App\Models\Reservation::overlaps($validated['place_id'], $start, $end, $validated['battement'])) {
            return back()->withInput()->withErrors(['place_id' => 'Le créneau sélectionné (avec battement) chevauche une réservation existante pour cette place.']);
        }

        $reservation = \App\Models\Reservation::create([
            'user_id' => $request->user()->id,
            'place_id' => $validated['place_id'],
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'en_attente',
            'battement_minutes' => $validated['battement'],
            'paiement_effectue' => $request->has('paiement_effectue'),
        ]);

        return redirect()->route('reservations.index')->with('success', 'Réservation effectuée avec succès');
    }
}
