<?php

namespace App\Services;

use App\Models\Place;
use App\Models\PlaceAvailability;
use App\Models\PlaceUnavailability;
use App\Models\Reservation;
use App\Support\BookingPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service pour calculer les disponibilités des places.
 * Extrait du ReservationController pour améliorer la maintenabilité.
 */
class PlaceBlockedSlotsService
{
    /**
     * Récupère les places disponibles pour une date donnée avec leurs créneaux.
     *
     * @param  int|null  $excludeUserId  Exclure les places appartenant a cet utilisateur.
     * @param  array<int, string>  $groupCodes
     * @return array{places: Collection<int, Place>, placeHours: array<int, array<int, string>>, placeSegments: array<int, array<int, string>>}
     */
    public function getAvailablePlacesForDate(Carbon $date, ?int $excludeUserId = null, array $groupCodes = [], ?string $userEmail = null): array
    {
        $places = Place::where('is_active', true)
            ->when($excludeUserId !== null, function ($query) use ($excludeUserId) {
                $query->where('user_id', '!=', $excludeUserId);
            })
            ->with(['blockedSlots', 'unavailabilities', 'site'])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->get()
            ->filter(fn (Place $place) => $place->isVisibleWithAnyGroupCodes($groupCodes, $userEmail))
            ->values();

        $placeHours = [];
        $filteredPlaces = collect();
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $reservationsByPlace = Reservation::query()
            ->whereIn('place_id', $places->pluck('id'))
            ->active()
            ->where('date_debut', '<', $dayEnd->copy()->addMinutes(5))
            ->where('date_fin', '>=', $dayStart->copy()->subDay())
            ->get(['place_id', 'date_debut', 'date_fin', 'battement_minutes'])
            ->groupBy('place_id');

        $placeSegments = [];
        foreach ($places as $place) {
            $reservations = $reservationsByPlace->get($place->id, collect());

            $placeSegments[$place->id] = collect(BookingPeriod::SEGMENTS)
                ->keys()
                ->filter(function ($segment) use ($place, $date, $reservations) {
                    [$start, $end] = BookingPeriod::range($date, $segment);

                    return $start->gt(now())
                        && $place->isAvailableFor($start, $end)
                        && ! $this->isBlockedByReservation($start, $end->copy()->addMinutes(5), $reservations);
                })
                ->values()->all();

            $hours = $this->computeAvailableHours($place, $date, $reservations);
            if (! empty($placeSegments[$place->id]) || (! $place->hasRentalRules() && ! empty($hours))) {
                $placeHours[$place->id] = $hours;
                $filteredPlaces->push($place);
            }
        }

        return [
            'places' => $filteredPlaces,
            'placeHours' => $placeHours,
            'placeSegments' => $placeSegments,
        ];
    }

    /**
     * Calcule les heures de début disponibles (créneaux d'1h) pour une place et une date.
     *
     * @param  Collection<int, Reservation>|null  $reservations
     * @return array<int, string>
     */
    public function computeAvailableHours(Place $place, Carbon $date, ?Collection $reservations = null): array
    {
        if (! $place->isReservableForDate($date)) {
            return [];
        }

        $day = (int) $date->dayOfWeek;
        $weeklyBlockedSlots = $place->blockedSlots->where('day_of_week', $day);

        $exceptions = $place->unavailabilities->filter(fn ($e) => $e->date->toDateString() === $date->toDateString());
        $fullDayBlock = $exceptions->whereNull('start_time')->whereNull('end_time')->isNotEmpty();

        if ($fullDayBlock) {
            return [];
        }

        $timeExceptions = $exceptions
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        $hours = [];
        $hasRentalRules = $place->hasRentalRules();
        $allowedSegments = $hasRentalRules ? $place->allowedSegmentsForDate($date) : [];

        foreach (range(0, 23) as $h) {
            if ($hasRentalRules) {
                $hourStartMin = $h * 60;
                $hourEndMin = ($h + 1) * 60;
                $fitsRule = false;
                foreach ($allowedSegments as $segmentKey) {
                    $period = BookingPeriod::SEGMENTS[$segmentKey] ?? null;
                    if ($period !== null && $hourStartMin >= $period['start'] && $hourEndMin <= $period['end']) {
                        $fitsRule = true;
                        break;
                    }
                }
                if (! $fitsRule) {
                    continue;
                }
            }

            $start = $date->copy()->setTime($h, 0);
            $end = $start->copy()->addHour();

            if ($this->overlapsWeeklyBlockedSlot($start, $end, $weeklyBlockedSlots, $date)) {
                continue;
            }

            if ($this->isBlockedByException($start, $end, $timeExceptions, $date)) {
                continue;
            }

            if ($this->isBlockedByReservation($start, $end, $reservations)) {
                continue;
            }

            $hours[] = sprintf('%02d:00', $h);
        }

        return $hours;
    }

    /**
     * Vérifie si un créneau est dans une plage de disponibilité.
     *
     * @param  Collection<int, PlaceAvailability>  $slots
     */
    private function overlapsWeeklyBlockedSlot(Carbon $start, Carbon $end, Collection $slots, Carbon $date): bool
    {
        foreach ($slots as $slot) {
            $slotStart = Carbon::parse($date->toDateString().' '.$slot->start_time);
            $slotEnd = Carbon::parse($date->toDateString().' '.$slot->end_time);

            if ($start < $slotEnd && $end > $slotStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un créneau est bloqué par une exception.
     *
     * @param  Collection<int, PlaceUnavailability>  $exceptions
     */
    private function isBlockedByException(Carbon $start, Carbon $end, Collection $exceptions, Carbon $date): bool
    {
        foreach ($exceptions as $exception) {
            $exStart = Carbon::parse($date->toDateString().' '.$exception->start_time);
            $exEnd = Carbon::parse($date->toDateString().' '.$exception->end_time);

            if ($start < $exEnd && $end > $exStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un créneau est bloqué par une réservation existante (battement inclus).
     *
     * @param  Collection<int, Reservation>|null  $reservations
     */
    private function isBlockedByReservation(Carbon $start, Carbon $end, ?Collection $reservations): bool
    {
        if ($reservations === null || $reservations->isEmpty()) {
            return false;
        }

        foreach ($reservations as $reservation) {
            $resStart = $reservation->date_debut->copy();
            $resEnd = $reservation->date_fin->copy()->addMinutes((int) ($reservation->battement_minutes ?? 0));

            if ($start < $resEnd && $end > $resStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trouve le premier créneau disponible dans un segment horaire.
     *
     * @param  array<int, string>  $hours
     */
    public function findFirstHourInSegment(array $hours, string $segment): ?string
    {
        $segmentRanges = [
            'matin_travail' => [480, 720],
            'aprem_travail' => [720, 1050],
            'soir' => [1080, 1440],
            'nuit' => [0, 450],
        ];

        if (! isset($segmentRanges[$segment])) {
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
