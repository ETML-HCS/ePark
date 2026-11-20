<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStatsController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin();

        [$year, $siteId, $status, $start, $end] = $this->resolveFilters($request);

        $cacheVersion = (int) Cache::get('admin_stats_version', 1);
        $cacheKey = sprintf('admin_stats_v%d_%d_%s_%s', $cacheVersion, $year, (string) $siteId, (string) $status);

        $computed = Cache::remember($cacheKey, 300, function () use ($start, $end, $siteId, $status) {
            $reservationsQuery = $this->buildFilteredQuery($start, $end, $siteId, $status);

            // Exclure les réservations annulées des métriques d'occupation/revenus sauf si filtrées explicitement
            $activeMetricsQuery = (clone $reservationsQuery);
            if ($status !== 'annulée') {
                $activeMetricsQuery->where('statut', '!=', 'annulée');
            }

            $totalReservations = (clone $activeMetricsQuery)->count();
            $totalRevenueCents = (int) (clone $activeMetricsQuery)
                ->where('payment_status', 'paid')
                ->sum('amount_cents');

            $reservationsForOccupancy = (clone $activeMetricsQuery)
                ->get(['date_debut', 'date_fin']);

            $reservedMinutes = $reservationsForOccupancy->sum(function (Reservation $reservation) {
                return $reservation->date_debut->diffInMinutes($reservation->date_fin);
            });

            $availableMinutes = $this->computeAvailableMinutes($start, $end, $siteId);
            $occupancyRate = $availableMinutes > 0
                ? round(($reservedMinutes / $availableMinutes) * 100, 1)
                : 0.0;

            $topPlaces = (clone $activeMetricsQuery)
                ->select('place_id', DB::raw('count(*) as reservations_count'), DB::raw('sum(amount_cents) as revenue_cents'))
                ->groupBy('place_id')
                ->orderByDesc('reservations_count')
                ->with(['place.site'])
                ->limit(10)
                ->get();

            return [
                'totalReservations' => $totalReservations,
                'totalRevenueCents' => $totalRevenueCents,
                'reservedHours' => round($reservedMinutes / 60, 1),
                'occupancyRate' => $occupancyRate,
                'topPlaces' => $topPlaces,
            ];
        });

        $currentYear = (int) now()->year;
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
            'totalReservations' => $computed['totalReservations'],
            'totalRevenueCents' => $computed['totalRevenueCents'],
            'reservedHours' => $computed['reservedHours'],
            'occupancyRate' => $computed['occupancyRate'],
            'topPlaces' => $computed['topPlaces'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureAdmin();

        [$year, $siteId, $status, $start, $end] = $this->resolveFilters($request);

        $reservations = $this->buildFilteredQuery($start, $end, $siteId, $status)
            ->with(['user:id,name,email', 'place.site'])
            ->orderBy('date_debut')
            ->get();

        $rows = [];
        foreach ($reservations as $reservation) {
            /** @var Reservation $reservation */
            $place = $reservation->place;
            $site = $place?->site;
            $tenant = $reservation->user;
            $rows[] = [
                $reservation->id,
                $reservation->date_debut->format('d.m.Y H:i'),
                $reservation->date_fin->format('d.m.Y H:i'),
                $site !== null ? $site->nom : '',
                $place !== null ? $place->nom : 'Place archivée',
                $tenant !== null ? $tenant->name : '',
                $tenant !== null ? $tenant->email : '',
                $reservation->vehicle_plate ?? '',
                $reservation->statut,
                $reservation->payment_status,
                $reservation->payment_received_at ? 'Oui' : 'Non',
                number_format(($reservation->amount_cents ?? 0) / 100, 2, '.', ''),
                number_format(($reservation->penalty_cents ?? 0) / 100, 2, '.', ''),
            ];
        }

        return CsvExporter::download(
            sprintf('epark-admin-stats-%d.csv', $year),
            ['ID', 'Début', 'Fin', 'Site', 'Place', 'Locataire', 'E-mail', 'Plaque', 'Statut', 'Paiement', 'Réception confirmée', 'Montant (CHF)', 'Pénalité (CHF)'],
            $rows
        );
    }

    private function ensureAdmin(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (! $user || $user->role !== 'admin') {
            abort(403, 'Non autorisé.');
        }
    }

    /**
     * @return array{0: int, 1: string|null, 2: string|null, 3: Carbon, 4: Carbon}
     */
    private function resolveFilters(Request $request): array
    {
        $currentYear = (int) now()->year;
        $year = (int) $request->query('year', (string) $currentYear);
        if ($year < 2000 || $year > $currentYear) {
            $year = $currentYear;
        }

        $siteIdRaw = $request->query('site_id');
        $siteId = is_scalar($siteIdRaw) && (string) $siteIdRaw !== '' ? (string) $siteIdRaw : null;

        $statusRaw = $request->query('status');
        $allowedStatuses = ['en_attente', 'confirmée', 'annulée', 'paid'];
        $status = is_string($statusRaw) && in_array($statusRaw, $allowedStatuses, true) ? $statusRaw : null;

        $start = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $end = Carbon::createFromDate($year, 12, 31)->endOfDay();

        return [$year, $siteId, $status, $start, $end];
    }

    /**
     * @return Builder<Reservation>
     */
    private function buildFilteredQuery(Carbon $start, Carbon $end, ?string $siteId, ?string $status): Builder
    {
        $query = Reservation::query()
            ->whereBetween('date_debut', [$start, $end]);

        if ($siteId !== null) {
            $query->whereHas('place', function ($placeQuery) use ($siteId) {
                $placeQuery->where('site_id', $siteId);
            });
        }

        if ($status !== null) {
            if ($status === 'paid') {
                $query->where('payment_status', 'paid');
            } else {
                $query->where('statut', $status);
            }
        }

        return $query;
    }

    private function computeAvailableMinutes(Carbon $start, Carbon $end, ?string $siteId): int
    {
        $placesQuery = Place::query()
            ->where('is_active', true)
            ->with(['blockedSlots', 'unavailabilities']);
        if ($siteId !== null) {
            $placesQuery->where('site_id', $siteId);
        }
        $places = $placesQuery->get();

        $availableMinutes = 0;
        foreach ($places as $place) {
            $rangeStart = $start->copy();
            $rangeEnd = $end->copy();

            if (! empty($place->availability_start_date)) {
                $rangeStart = $rangeStart->max($place->availability_start_date->copy()->startOfDay());
            }
            if (! empty($place->availability_end_date)) {
                $rangeEnd = $rangeEnd->min($place->availability_end_date->copy()->endOfDay());
            }

            if ($rangeStart->gt($rangeEnd)) {
                continue;
            }

            $blockedSlotsByDay = $place->blockedSlots->groupBy('day_of_week');
            $unavailabilitiesByDate = $place->unavailabilities->groupBy(fn ($item) => $item->date->toDateString());

            $cursor = $rangeStart->copy()->startOfDay();
            $endCursor = $rangeEnd->copy()->startOfDay();

            while ($cursor->lte($endCursor)) {
                $intervals = [];
                $dayOfWeek = $cursor->dayOfWeek;

                foreach ($blockedSlotsByDay->get($dayOfWeek, []) as $slot) {
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

        return $availableMinutes;
    }

    private function timeToMinutes(?string $time): int
    {
        if (empty($time) || ! preg_match('/^\d{2}:\d{2}/', $time)) {
            return 0;
        }

        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervals
     */
    private function calculateBlockedMinutes(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

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

        return (int) min(1440, $blocked);
    }
}
