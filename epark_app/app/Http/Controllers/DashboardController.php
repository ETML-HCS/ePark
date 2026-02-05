<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $isOwner = in_array($user->role, ['proprietaire', 'les deux'], true);
        $cacheKey = "dashboard_stats_{$user->id}";

        // Cache des statistiques pour 5 minutes
        $stats = Cache::remember($cacheKey, 300, function () use ($user, $isOwner) {
            $nbPlaces = $user->places()->count();

            $nbReservationsAttente = $isOwner
                ? Reservation::whereHas('place', fn($q) => $q->where('user_id', $user->id))
                    ->where('statut', 'en_attente')
                    ->count()
                : $user->reservations()->where('statut', 'en_attente')->count();

            $revenusMois = 0;
            if ($isOwner) {
                $revenusMois = Reservation::whereHas('place', fn($q) => $q->where('user_id', $user->id))
                    ->where('statut', 'confirmée')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount_cents') / 100;
            }

            return compact('nbPlaces', 'nbReservationsAttente', 'revenusMois');
        });

        // Réservations récentes avec eager loading optimisé
        $reservations = $this->getRecentReservations($user);

        return view('dashboard', [
            ...$stats,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Récupère les réservations récentes selon le rôle.
     */
    private function getRecentReservations(User $user)
    {
        $query = Reservation::with(['user:id,name,email', 'place:id,nom,site_id', 'place.site:id,nom,adresse'])
            ->orderByDesc('created_at')
            ->limit(5);

        if ($user->role === 'admin') {
            return $query->get();
        }

        // Propriétaire ou hybride: voir les réservations sur ses places + ses propres réservations
        if (in_array($user->role, ['proprietaire', 'les deux'], true)) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('place', fn($q) => $q->where('user_id', $user->id));
            })->get();
        }

        // Locataire: seulement ses réservations
        return $query->where('user_id', $user->id)->get();
    }
}
