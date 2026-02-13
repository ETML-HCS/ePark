<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Place;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        /** @var \App\Models\User $user */
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
        /** @var \App\Models\User $user */
        $user = $request->user();

        $user->fill($request->validated());

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

        if (!$matchingPlace) {
            return Redirect::route('profile.edit')->withErrors([
                'secret_group_code' => 'Nom de groupe ou code invalide.',
            ], 'secretGroup');
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        $entries = collect($user->secretGroupEntries());
        $alreadyExists = $entries->contains(fn (array $entry) => mb_strtolower($entry['code']) === mb_strtolower($groupCode));

        if (!$alreadyExists) {
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

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (!$user) {
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

        /** @var \App\Models\User $user */
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
