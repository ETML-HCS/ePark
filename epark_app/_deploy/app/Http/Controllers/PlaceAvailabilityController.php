<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\PlaceUnavailability;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlaceAvailabilityController extends Controller
{
    public function edit(Place $place): View
    {
        $this->authorizeOwner($place);

        $availabilities = $place->availabilities()->orderBy('day_of_week')->get();
        $unavailabilities = $place->unavailabilities()->orderByDesc('date')->get();
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            0 => 'Dimanche',
        ];

        $availabilityByDay = $availabilities->keyBy('day_of_week');

        return view('places.availability', compact('place', 'days', 'availabilityByDay', 'unavailabilities'));
    }

    public function update(Request $request, Place $place): RedirectResponse
    {
        $this->authorizeOwner($place);

        $availability = $request->input('availability', []);

        $place->availabilities()->delete();

        foreach ($availability as $day => $slot) {
            $start = $slot['start'] ?? null;
            $end = $slot['end'] ?? null;

            if (!$start || !$end) {
                continue;
            }

            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start)) {
                continue;
            }

            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end)) {
                continue;
            }

            if ($end <= $start) {
                continue;
            }

            $place->availabilities()->create([
                'day_of_week' => (int) $day,
                'start_time' => $start,
                'end_time' => $end,
            ]);
        }

        return back()->with('success', 'Disponibilités mises à jour.');
    }

    public function storeException(Request $request, Place $place): RedirectResponse
    {
        $this->authorizeOwner($place);

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);

        if (!empty($validated['start_time']) && !empty($validated['end_time']) && $validated['end_time'] <= $validated['start_time']) {
            return back()->withErrors(['end_time' => 'L\'heure de fin doit être après l\'heure de début.']);
        }

        $place->unavailabilities()->create([
            'date' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Indisponibilité ajoutée.');
    }

    public function destroyException(Place $place, PlaceUnavailability $unavailability): RedirectResponse
    {
        $this->authorizeOwner($place);

        if ($unavailability->place_id !== $place->id) {
            abort(404);
        }

        $unavailability->delete();

        return back()->with('success', 'Indisponibilité supprimée.');
    }

    private function authorizeOwner(Place $place): void
    {
        if (auth()->id() !== $place->user_id) {
            abort(403, 'Non autorisé.');
        }
    }
}
