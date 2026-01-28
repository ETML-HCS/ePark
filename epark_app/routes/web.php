<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Accueil : liste des places disponibles
Route::get('/', [App\Http\Controllers\PlaceController::class, 'index'])->name('home');

// Dashboard utilisateur (places proposées, réservations)
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// Proposer une place (auth obligatoire)
Route::middleware(['auth'])->group(function () {
    Route::get('/places/create', [App\Http\Controllers\PlaceController::class, 'create'])->name('places.create');
    Route::post('/places', [App\Http\Controllers\PlaceController::class, 'store'])->name('places.store');
});

// Réserver une place (auth obligatoire)
Route::middleware(['auth'])->group(function () {
    Route::get('/reservations/create', [App\Http\Controllers\ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [App\Http\Controllers\ReservationController::class, 'store'])->name('reservations.store');
    // Validation/refus/annulation
    Route::post('/reservations/{id}/valider', [App\Http\Controllers\ReservationController::class, 'valider'])->name('reservations.valider');
    Route::post('/reservations/{id}/refuser', [App\Http\Controllers\ReservationController::class, 'refuser'])->name('reservations.refuser');
    Route::delete('/reservations/{id}', [App\Http\Controllers\ReservationController::class, 'destroy'])->name('reservations.destroy');
});

// Mes réservations et mes places (auth obligatoire)
Route::middleware(['auth'])->group(function () {
    Route::get('/reservations', [App\Http\Controllers\ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/places', [App\Http\Controllers\PlaceController::class, 'mesPlaces'])->name('places.mes');
});

// Sites (propriétaires) - gestion des sites
Route::middleware(['auth'])->group(function () {
    Route::get('/sites', [App\Http\Controllers\SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [App\Http\Controllers\SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [App\Http\Controllers\SiteController::class, 'store'])->name('sites.store');
});

// ---------------------------------------------------------
// Profil Utilisateur
// ---------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
