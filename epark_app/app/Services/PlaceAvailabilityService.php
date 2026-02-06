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
     * @param int|null $excludeUserId Exclure les places appartenant a cet utilisateur.
     * @return array{places: Collection<int, Place>, placeHours: array<int, array<int, string>>}
     */
    public function getAvailablePlacesForDate(Carbon $date, ?int $excludeUserId = null): array
    {
        $places = Place::where('is_active', true)
            ->when($excludeUserId !== null, function ($query) use ($excludeUserId) {
                $query->where('user_id', '!=', $excludeUserId);
            })
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
        $blockedSlots = $place->availabilities->where('day_of_week', $day);

        $exceptions = $place->unavailabilities->where('date', $date->toDateString());
        $fullDayBlock = $exceptions->whereNull('start_time')->whereNull('end_time')->isNotEmpty();

        if ($fullDayBlock) {
            return [];
        }

        $timeExceptions = $exceptions
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        $hours = [];

        foreach (range(0, 23) as $h) {
            $start = $date->copy()->setTime($h, 0);
            $end = $start->copy()->addHour();

            if ($this->isInsideBlockedSlot($start, $end, $blockedSlots, $date)) {
                continue;
            }

            if ($this->isBlockedByException($start, $end, $timeExceptions, $date)) {
                continue;
            }

            $hours[] = sprintf('%02d:00', $h);
        }

        return $hours;
    }

    /**
     * Vérifie si un créneau est dans une plage de disponibilité.
     */
    private function isInsideBlockedSlot(Carbon $start, Carbon $end, Collection $slots, Carbon $date): bool
    {
        foreach ($slots as $slot) {
            $slotStart = Carbon::parse($date->toDateString() . ' ' . $slot->start_time);
            $slotEnd = Carbon::parse($date->toDateString() . ' ' . $slot->end_time);

            if ($start < $slotEnd && $end > $slotStart) {
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
            'matin_travail' => [480, 720],
            'aprem_travail' => [720, 1050],
            'soir' => [1080, 1440],
            'nuit' => [0, 450],
        ];

        if (!isset($segmentRanges[$segment])) {
            return null;
        }

        [$segStart, $segEnd] = $segmentRanges[$segment];

        foreach ($hours as $hour) {
            [$hh, $mm] = array_map('intval', explode(':', $hour));
            $minutes = ($hh * 60) + $mm;
            if ($minutes >= $segStart && ($minutes + 60) <= $segEnd) {
                return $hour;
            }
        }

        return null;
    }
}
