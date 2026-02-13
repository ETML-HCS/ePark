<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Non autorise.');
        }

        $currentYear = (int) now()->year;
        $year = (int) request('year', $currentYear);
        if ($year < 2000 || $year > $currentYear) {
            $year = $currentYear;
        }

        $siteId = request('site_id');
        $status = request('status');

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $reservationsQuery = Reservation::query()
            ->whereBetween('date_debut', [$start, $end]);

        if (!empty($siteId)) {
            $reservationsQuery->whereHas('place', function ($query) use ($siteId) {
                $query->where('site_id', $siteId);
            });
        }

        if (!empty($status)) {
            if ($status === 'paid') {
                $reservationsQuery->where('payment_status', 'paid');
            } else {
                $reservationsQuery->where('statut', $status);
            }
        }

        $totalReservations = (clone $reservationsQuery)->count();
        $totalRevenueCents = (clone $reservationsQuery)
            ->where('payment_status', 'paid')
            ->sum('amount_cents');

        $reservationsForOccupancy = (clone $reservationsQuery)
            ->get(['date_debut', 'date_fin']);

        $reservedMinutes = $reservationsForOccupancy->sum(function ($reservation) {
            if (!$reservation->date_debut || !$reservation->date_fin) {
                return 0;
            }

            return $reservation->date_debut->diffInMinutes($reservation->date_fin);
        });

        $placesQuery = Place::query()
            ->where('is_active', true)
            ->with(['availabilities', 'unavailabilities']);
        if (!empty($siteId)) {
            $placesQuery->where('site_id', $siteId);
        }
        $places = $placesQuery->get();

        $availableMinutes = 0;
        foreach ($places as $place) {
            $rangeStart = $start->copy();
            $rangeEnd = $end->copy();

            if (!empty($place->availability_start_date)) {
                $rangeStart = $rangeStart->max($place->availability_start_date->copy()->startOfDay());
            }
            if (!empty($place->availability_end_date)) {
                $rangeEnd = $rangeEnd->min($place->availability_end_date->copy()->endOfDay());
            }

            if ($rangeStart->gt($rangeEnd)) {
                continue;
            }

            $availabilitiesByDay = $place->availabilities->groupBy('day_of_week');
            $unavailabilitiesByDate = $place->unavailabilities->groupBy(fn($item) => $item->date->toDateString());

            $cursor = $rangeStart->copy()->startOfDay();
            $endCursor = $rangeEnd->copy()->startOfDay();

            while ($cursor->lte($endCursor)) {
                $intervals = [];
                $dayOfWeek = $cursor->dayOfWeek;

                foreach ($availabilitiesByDay->get($dayOfWeek, []) as $slot) {
                    $startMinutes = $this->timeToMinutes($slot->start_time);
                    $endMinutes = $this->timeToMinutes($slot->end_time);
                    if ($startMinutes < $endMinutes) {
                        $intervals[] = [$startMinutes, $endMinutes];
                    }
                }

                $exceptions = $unavailabilitiesByDate->get($cursor->toDateString(), collect());
                foreach ($exceptions as $exception) {
                    if (empty($exception->start_time) || empty($exception->end_time)) {
                        $intervals = [[0, 1440]];
                        break;
                    }

                    $startMinutes = $this->timeToMinutes($exception->start_time);
                    $endMinutes = $this->timeToMinutes($exception->end_time);
                    if ($startMinutes < $endMinutes) {
                        $intervals[] = [$startMinutes, $endMinutes];
                    }
                }

                $blockedMinutes = $this->calculateBlockedMinutes($intervals);
                $availableMinutes += max(0, 1440 - $blockedMinutes);

                $cursor->addDay();
            }
        }

        $occupancyRate = $availableMinutes > 0
            ? round(($reservedMinutes / $availableMinutes) * 100, 1)
            : 0.0;

        $topPlaces = (clone $reservationsQuery)
            ->select('place_id', DB::raw('count(*) as reservations_count'), DB::raw('sum(amount_cents) as revenue_cents'))
            ->groupBy('place_id')
            ->orderByDesc('reservations_count')
            ->with(['place.site'])
            ->limit(10)
            ->get();

        $sites = Site::orderBy('nom')->get(['id', 'nom']);
        $yearOptions = range($currentYear, $currentYear - 4);

        return view('admin.stats', [
            'sites' => $sites,
            'yearOptions' => $yearOptions,
            'selectedYear' => $year,
            'selectedSiteId' => $siteId,
            'selectedStatus' => $status,
            'start' => $start,
            'end' => $end,
            'totalReservations' => $totalReservations,
            'totalRevenueCents' => $totalRevenueCents,
            'reservedHours' => round($reservedMinutes / 60, 1),
            'occupancyRate' => $occupancyRate,
            'topPlaces' => $topPlaces,
        ]);
    }

    private function timeToMinutes(?string $time): int
    {
        if (empty($time) || !preg_match('/^\d{2}:\d{2}/', $time)) {
            return 0;
        }

        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));
        return ($hours * 60) + $minutes;
    }

    private function calculateBlockedMinutes(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);

        $blocked = 0;
        [$currentStart, $currentEnd] = $intervals[0];

        foreach (array_slice($intervals, 1) as [$start, $end]) {
            if ($start <= $currentEnd) {
                $currentEnd = max($currentEnd, $end);
                continue;
            }

            $blocked += max(0, $currentEnd - $currentStart);
            $currentStart = $start;
            $currentEnd = $end;
        }

        $blocked += max(0, $currentEnd - $currentStart);

        return min(1440, $blocked);
    }
}
