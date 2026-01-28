<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        // Statistiques
        $nbPlaces = $user->places()->count();
        $nbReservationsAttente = $user->role === 'proprietaire' || $user->role === 'les deux'
            ? \App\Models\Reservation::whereHas('place', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('statut', 'en_attente')->count()
            : $user->reservations()->where('statut', 'en_attente')->count();
        $revenusMois = 0;
        if ($user->role === 'proprietaire' || $user->role === 'les deux') {
            $revenusMois = \App\Models\Reservation::whereHas('place', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->where('statut', 'validée')
                ->whereMonth('created_at', now()->month)
                ->sum('paiement_effectue'); // Remplacer par le montant si besoin
        }
        // Réservations récentes
        if ($user->role === 'admin') {
            $reservations = \App\Models\Reservation::with(['user', 'place'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        } else {
            $reservations = \App\Models\Reservation::with(['user', 'place'])
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact('reservations', 'nbPlaces', 'nbReservationsAttente', 'revenusMois'));
    }
}
