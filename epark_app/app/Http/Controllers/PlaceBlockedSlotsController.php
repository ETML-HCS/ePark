<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\PlaceUnavailability;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PlaceBlockedSlotsController extends Controller
{
    public function edit(Place $place): View
    {
        $this->authorizeOwner($place);

        $weeklyBlockedSlots = $place->blockedSlots()->orderBy('day_of_week')->get();
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

        $weeklyBlockedSlotsByDay = $weeklyBlockedSlots->groupBy('day_of_week');

        return view('places.availability', compact('place', 'days', 'weeklyBlockedSlotsByDay', 'unavailabilities'));
    }

    public function update(Request $request, Place $place): RedirectResponse
    {
        $this->authorizeOwner($place);

        $request->merge([
            'visual_day_start_time' => $this->normalizeHourMinute($request->input('visual_day_start_time')),
            'visual_day_end_time' => $this->normalizeHourMinute($request->input('visual_day_end_time')),
        ]);

        $validatedOptions = $request->validate([
            'availability_start_date' => 'nullable|date',
            'availability_end_date' => 'nullable|date|after_or_equal:availability_start_date',
            'weekly_schedule_type' => 'nullable|in:full_week,work_week',
            'visual_day_start_time' => 'nullable|date_format:H:i',
            'visual_day_end_time' => 'nullable|date_format:H:i',
            'pending_validation_default' => 'nullable|in:manual,confirm,refuse',
            'is_group_reserved' => 'nullable|boolean',
            'group_name' => 'nullable|string|max:120',
            'group_access_code' => 'nullable|string|min:4|max:32',
            'group_allowed_email_domains_raw' => 'nullable|string|max:1000',
        ]);

        $weeklyBlockedSlots = $request->input('weekly_blocked_slots', $request->input('availability', []));

        if (empty($weeklyBlockedSlots)) {
            $legacySlots = $request->input('slots', []);
            if (is_array($legacySlots)) {
                foreach ($legacySlots as $legacySlot) {
                    if (!is_array($legacySlot)) {
                        continue;
                    }
                    $legacyDay = $legacySlot['day_of_week'] ?? null;
                    if ($legacyDay === null) {
                        continue;
                    }
                    $weeklyBlockedSlots[(int) $legacyDay][] = [
                        'start' => $legacySlot['start'] ?? $legacySlot['start_time'] ?? null,
                        'end' => $legacySlot['end'] ?? $legacySlot['end_time'] ?? null,
                    ];
                }
            }
        }

        $place->blockedSlots()->delete();

        foreach ($weeklyBlockedSlots as $day => $slots) {
            $normalizedSlots = [];

            if (is_array($slots) && array_key_exists('start', $slots)) {
                $normalizedSlots[] = $slots;
            } elseif (is_array($slots)) {
                $normalizedSlots = $slots;
            }

            foreach ($normalizedSlots as $slot) {
                $start = $slot['start'] ?? $slot['start_time'] ?? null;
                $end = $slot['end'] ?? $slot['end_time'] ?? null;

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

                $place->blockedSlots()->create([
                    'day_of_week' => (int) $day,
                    'start_time' => $start,
                    'end_time' => $end,
                ]);
            }
        }

        $place->availability_start_date = $validatedOptions['availability_start_date'] ?? null;
        $place->availability_end_date = $validatedOptions['availability_end_date'] ?? null;
        $place->weekly_schedule_type = $validatedOptions['weekly_schedule_type'] ?? null;
        $place->visual_day_start_time = $validatedOptions['visual_day_start_time'] ?? null;
        $place->visual_day_end_time = $validatedOptions['visual_day_end_time'] ?? null;
        $place->pending_validation_default = $validatedOptions['pending_validation_default'] ?? 'manual';

        $isGroupReserved = $request->boolean('is_group_reserved');
        $place->is_group_reserved = $isGroupReserved;

        $groupAllowedEmailDomains = collect(preg_split('/[\r\n,;]+/', (string) ($validatedOptions['group_allowed_email_domains_raw'] ?? '')) ?: [])
            ->map(fn ($domain) => ltrim(mb_strtolower(trim((string) $domain)), '@'))
            ->filter(fn ($domain) => $domain !== '')
            ->unique()
            ->values()
            ->all();

        if ($isGroupReserved) {
            $place->group_name = $validatedOptions['group_name'] ?? null;
            $place->group_allowed_email_domains = $groupAllowedEmailDomains;

            if ($request->filled('group_access_code')) {
                $place->group_access_code_hash = Hash::make((string) $validatedOptions['group_access_code']);
                $this->syncOwnerSecretGroup(
                    request: $request,
                    groupName: (string) ($place->group_name ?? ''),
                    groupCode: (string) $validatedOptions['group_access_code']
                );
            }
        } else {
            $place->group_name = null;
            $place->group_access_code_hash = null;
            $place->group_allowed_email_domains = [];
        }

        $place->save();

        return back()->with('success', 'Règles hebdomadaires mises à jour.');
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
        if (Auth::id() !== $place->user_id) {
            abort(403, 'Non autorisé.');
        }
    }

    private function syncOwnerSecretGroup(Request $request, string $groupName, string $groupCode): void
    {
        $groupName = trim($groupName);
        $groupCode = trim($groupCode);

        if ($groupName === '' || $groupCode === '') {
            return;
        }

        /** @var \App\Models\User|null $owner */
        $owner = $request->user();
        if (!$owner) {
            return;
        }

        $entries = collect($owner->secretGroupEntries());

        $updatedByCode = false;
        $entries = $entries->map(function (array $entry) use ($groupName, $groupCode, &$updatedByCode) {
            if (mb_strtolower((string) $entry['code']) === mb_strtolower($groupCode)) {
                $updatedByCode = true;
                return [
                    'name' => $groupName,
                    'code' => $groupCode,
                ];
            }

            return $entry;
        });

        if (!$updatedByCode) {
            $updatedByName = false;
            $entries = $entries->map(function (array $entry) use ($groupName, $groupCode, &$updatedByName) {
                if (mb_strtolower((string) $entry['name']) === mb_strtolower($groupName)) {
                    $updatedByName = true;
                    return [
                        'name' => $groupName,
                        'code' => $groupCode,
                    ];
                }

                return $entry;
            });

            if (!$updatedByName) {
                $entries->push([
                    'name' => $groupName,
                    'code' => $groupCode,
                ]);
            }
        }

        $owner->secret_group_codes = $entries->values()->all();
        $owner->save();
    }

    private function normalizeHourMinute(mixed $value): ?string
    {
        $time = trim((string) $value);

        if ($time === '') {
            return null;
        }

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) !== 1) {
            return $time;
        }

        return substr($time, 0, 5);
    }
}
