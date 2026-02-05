<?php

namespace App\Services;

use App\Models\Place;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service pour calculer les disponibilités des places.
 * Extrait du ReservationController pour améliorer la maintenabilité.
 */
class PlaceAvailabilityService
{
    /**
     * Récupère les places disponibles pour une date donnée avec leurs créneaux.
     *
     * @return array{places: Collection<int, Place>, placeHours: array<int, array<int, string>>}
     */
    public function getAvailablePlacesForDate(Carbon $date): array
    {
        $places = Place::where('is_active', true)
            ->with(['availabilities', 'unavailabilities', 'site'])
            ->get();

        $placeHours = [];
        $filteredPlaces = collect();

        foreach ($places as $place) {
            $hours = $this->computeAvailableHours($place, $date);
            if (!empty($hours)) {
                $placeHours[$place->id] = $hours;
                $filteredPlaces->push($place);
            }
        }

        return [
            'places' => $filteredPlaces,
            'placeHours' => $placeHours,
        ];
    }

    /**
     * Calcule les heures de début disponibles (créneaux d'1h) pour une place et une date.
     *
     * @return array<int, string>
     */
    public function computeAvailableHours(Place $place, Carbon $date): array
    {
        if (!$place->hasAvailabilityForDate($date)) {
            return [];
        }

        $day = (int) $date->dayOfWeek;
        $slots = $place->availabilities->where('day_of_week', $day);

        if ($slots->isEmpty()) {
            return [];
        }

        $exceptions = $place->unavailabilities
            ->where('date', $date->toDateString())
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        $hours = [];

        foreach (range(0, 23) as $h) {
            $start = $date->copy()->setTime($h, 0);
            $end = $start->copy()->addHour();

            if (!$this->isInsideSlot($start, $end, $slots, $date)) {
                continue;
            }

            if ($this->isBlockedByException($start, $end, $exceptions, $date)) {
                continue;
            }

            $hours[] = sprintf('%02d:00', $h);
        }

        return $hours;
    }

    /**
     * Vérifie si un créneau est dans une plage de disponibilité.
     */
    private function isInsideSlot(Carbon $start, Carbon $end, Collection $slots, Carbon $date): bool
    {
        foreach ($slots as $slot) {
            $slotStart = Carbon::parse($date->toDateString() . ' ' . $slot->start_time);
            $slotEnd = Carbon::parse($date->toDateString() . ' ' . $slot->end_time);

            if ($end->lessThanOrEqualTo($slotEnd) && $start->greaterThanOrEqualTo($slotStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un créneau est bloqué par une exception.
     */
    private function isBlockedByException(Carbon $start, Carbon $end, Collection $exceptions, Carbon $date): bool
    {
        foreach ($exceptions as $exception) {
            $exStart = Carbon::parse($date->toDateString() . ' ' . $exception->start_time);
            $exEnd = Carbon::parse($date->toDateString() . ' ' . $exception->end_time);

            if ($start < $exEnd && $end > $exStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trouve le premier créneau disponible dans un segment horaire.
     */
    public function findFirstHourInSegment(array $hours, string $segment): ?string
    {
        $segmentRanges = [
            'matin' => [0, 11],
            'apm' => [11, 18],
            'soir' => [18, 24],
        ];

        if (!isset($segmentRanges[$segment])) {
            return null;
        }

        [$segStart, $segEnd] = $segmentRanges[$segment];

        foreach ($hours as $hour) {
            $hh = (int) substr($hour, 0, 2);
            if ($hh >= $segStart && $hh < $segEnd) {
                return $hour;
            }
        }

        return null;
    }
}
