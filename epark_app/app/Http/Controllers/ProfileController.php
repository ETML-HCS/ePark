<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        if (array_key_exists('quick_vehicle_plate', $validated)) {
            $plate = trim((string) $validated['quick_vehicle_plate']);
            $validated['quick_vehicle_plate'] = $plate !== '' ? mb_strtoupper($plate) : null;
        }
        if (array_key_exists('iban', $validated)) {
            $iban = trim((string) $validated['iban']);
            $validated['iban'] = $iban !== '' ? mb_strtoupper($iban) : null;
        }
        if (array_key_exists('twint_phone', $validated)) {
            $phone = trim((string) $validated['twint_phone']);
            $validated['twint_phone'] = $phone !== '' ? $phone : null;
        }
        if (array_key_exists('payment_instructions', $validated)) {
            $instructions = trim((string) $validated['payment_instructions']);
            $validated['payment_instructions'] = $instructions !== '' ? $instructions : null;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Ajoute un groupe secret (nom + code) après vérification du couple.
     */
    public function storeSecretGroup(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('secretGroup', [
            'secret_group_name' => ['required', 'string', 'max:120'],
            'secret_group_code' => ['required', 'string', 'min:4', 'max:32', 'regex:/^\S+$/'],
        ]);

        $groupName = trim((string) $validated['secret_group_name']);
        $groupCode = trim((string) $validated['secret_group_code']);

        $matchingPlace = Place::query()
            ->where('is_group_reserved', true)
            ->whereNotNull('group_name')
            ->whereNotNull('group_access_code_hash')
            ->whereRaw('LOWER(group_name) = ?', [mb_strtolower($groupName)])
            ->get()
            ->first(fn (Place $place) => Hash::check($groupCode, (string) $place->group_access_code_hash));

        if (! $matchingPlace) {
            return Redirect::route('profile.edit')->withErrors([
                'secret_group_code' => 'Nom de groupe ou code invalide.',
            ], 'secretGroup');
        }

        /** @var User $user */
        $user = $request->user();

        $entries = collect($user->secretGroupEntries());
        $alreadyExists = $entries->contains(fn (array $entry) => mb_strtolower($entry['code']) === mb_strtolower($groupCode));

        if (! $alreadyExists) {
            $entries->push([
                'name' => (string) ($matchingPlace->group_name ?? $groupName),
                'code' => $groupCode,
            ]);
        }

        $user->secret_group_codes = $entries->values()->all();
        $user->save();

        return Redirect::route('profile.edit')->with('status', $alreadyExists ? 'secret-group-exists' : 'secret-group-added');
    }

    /**
     * Retire un groupe secret enregistré du profil utilisateur.
     */
    public function destroySecretGroup(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('secretGroup', [
            'secret_group_code_remove' => ['required', 'string', 'max:32'],
        ]);

        $codeToRemove = trim((string) $validated['secret_group_code_remove']);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return Redirect::route('profile.edit');
        }

        $entries = collect($user->secretGroupEntries());
        $remaining = $entries
            ->reject(fn (array $entry) => mb_strtolower((string) $entry['code']) === mb_strtolower($codeToRemove))
            ->values();

        if ($remaining->count() === $entries->count()) {
            return Redirect::route('profile.edit')->withErrors([
                'secret_group_code' => 'Groupe introuvable.',
            ], 'secretGroup');
        }

        $user->secret_group_codes = $remaining->all();
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'secret-group-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $hasActiveReservations = Reservation::query()
            ->where('statut', '!=', 'annulée')
            ->where('date_fin', '>=', now())
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('place', fn ($placeQuery) => $placeQuery->where('user_id', $user->id));
            })
            ->exists();

        if ($hasActiveReservations) {
            return Redirect::route('profile.edit')->withErrors([
                'account' => 'Impossible de supprimer votre compte tant que des réservations sont actives ou à venir.',
            ], 'userDeletion');
        }

        Auth::logout();
        Cookie::queue(Cookie::forget('epark_express_key'));

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
