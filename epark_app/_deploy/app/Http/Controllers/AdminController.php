<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Non autorisé.');
        }

        $stats = [
            'users' => User::count(),
            'sites' => Site::count(),
            'places' => Place::count(),
            'reservations' => Reservation::count(),
            'payments' => Payment::count(),
            'revenue' => Reservation::where('payment_status', 'paid')->sum('amount_cents') / 100,
        ];

        $recentReservations = Reservation::with(['user', 'place.site'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentPayments = Payment::with(['reservation.user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentUsers = User::orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentReservations', 'recentPayments', 'recentUsers'));
    }
}
