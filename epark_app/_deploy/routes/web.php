<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlaceAvailabilityController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------
// Routes Publiques
// ---------------------------------------------------------
Route::get('/', [PlaceController::class, 'index'])->name('home');
Route::view('/mentions-legales', 'legal.mentions-legales')->name('legal.mentions');

require __DIR__.'/auth.php';

// ---------------------------------------------------------
// Routes Protégées (Authentification requise)
// ---------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'onboarded'])->group(function () {

    // --- Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Back-office admin ---
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');

    // --- Gestion des Places ---
    Route::get('/places', [PlaceController::class, 'mesPlaces'])->name('places.mes');
    Route::get('/places/create', [PlaceController::class, 'create'])->name('places.create');
    Route::post('/places', [PlaceController::class, 'store'])->name('places.store');
    Route::get('/places/{place}/edit', [PlaceController::class, 'edit'])->name('places.edit');
    Route::put('/places/{place}', [PlaceController::class, 'update'])->name('places.update');

    // Disponibilités des places
    Route::prefix('/places/{place}')->group(function () {
        Route::get('/availability', [PlaceAvailabilityController::class, 'edit'])->name('places.availability.edit');
        Route::post('/availability', [PlaceAvailabilityController::class, 'update'])->name('places.availability.update');
        Route::post('/unavailability', [PlaceAvailabilityController::class, 'storeException'])->name('places.unavailability.store');
        Route::delete('/unavailability/{unavailability}', [PlaceAvailabilityController::class, 'destroyException'])->name('places.unavailability.destroy');
    });

    // --- Gestion des Réservations ---
    Route::prefix('/reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [ReservationController::class, 'show'])->name('show');
        Route::post('/{reservation}/payer', [ReservationController::class, 'payer'])->name('payer');
        Route::post('/{reservation}/valider', [ReservationController::class, 'valider'])->name('valider');
        Route::post('/{reservation}/refuser', [ReservationController::class, 'refuser'])->name('refuser');
        Route::delete('/{reservation}', [ReservationController::class, 'destroy'])->name('destroy');
        // Feedback après réservation terminée
        Route::get('/{reservation}/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
        Route::post('/{reservation}/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    });

    Route::post('/notifications/mark-all-read', function () {
        $user = Auth::user();
        $user?->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquees comme lues.');
    })->name('notifications.markAllRead');

    Route::get('/notifications', function () {
        $user = Auth::user();
        $notifications = $user?->notifications()->latest()->get() ?? collect();
        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    })->name('notifications.index');

    // --- Gestion des Sites ---
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');

    // --- Profil Utilisateur ---
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});