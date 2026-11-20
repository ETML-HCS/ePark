<?php

namespace App\Models;

use App\Support\BookingPeriod;
use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * App\Models\Place
 *
 * @property string $nom
 * @property float|string|null $feedbacks_avg_rating
 * @property int|null $feedbacks_count
 * @property array<int, array<string, mixed>>|null $rental_rules_json
 */
class Place extends Model
{
    /** @use HasFactory<PlaceFactory> */
    use HasFactory, SoftDeletes;

    public const MAX_RENTAL_RULES = 5;

    /** @var array<int, string> */
    public const DAY_SHORT_LABELS = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mer',
        4 => 'Jeu',
        5 => 'Ven',
        6 => 'Sam',
        0 => 'Dim',
    ];

    protected $fillable = [
        'user_id', // propriétaire
        'site_id',
        'nom',
        'type',
        'dimensions_json',
        'equipments_json',
        'hourly_price_cents',
        'rental_rules_json',
        'is_active',
        'cancel_deadline_hours',
        'pending_validation_default',
        'availability_start_date',
        'availability_end_date',
        'visual_day_start_time',
        'visual_day_end_time',
        'adresse',
        'description',
        'disponible', // temporaire, à remplacer par is_active
        'caracteristiques',
        'weekly_schedule_type',
        'is_group_reserved',
        'group_name',
        'group_access_code_hash',
        'group_allowed_email_domains',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'dimensions_json' => 'array',
        'equipments_json' => 'array',
        'hourly_price_cents' => 'integer',
        'rental_rules_json' => 'array',
        'is_active' => 'boolean',
        'cancel_deadline_hours' => 'integer',
        'pending_validation_default' => 'string',
        'availability_start_date' => 'date',
        'availability_end_date' => 'date',
        'disponible' => 'boolean',
        'is_group_reserved' => 'boolean',
        'group_allowed_email_domains' => 'array',
    ];

    /**
     * Retourne les plages de location personnalisées normalisées (max 5).
     *
     * @return array<int, array{label: string, days: array<int, int>, segments: array<int, string>, hourly_price_cents: int}>
     */
    public function normalizedRentalRules(): array
    {
        $raw = $this->rental_rules_json;
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $validSegments = array_keys(BookingPeriod::SEGMENTS);
        $normalized = [];

        foreach (array_slice($raw, 0, self::MAX_RENTAL_RULES) as $rule) {
            $rawDays = is_array($rule['days'] ?? null) ? $rule['days'] : [];
            $days = [];
            foreach ($rawDays as $d) {
                if (is_numeric($d)) {
                    $dayInt = (int) $d;
                    if ($dayInt >= 0 && $dayInt <= 6 && ! in_array($dayInt, $days, true)) {
                        $days[] = $dayInt;
                    }
                }
            }

            $rawSegments = is_array($rule['segments'] ?? null) ? $rule['segments'] : [];
            $segments = [];
            foreach ($rawSegments as $s) {
                $segStr = (string) $s;
                if (in_array($segStr, $validSegments, true) && ! in_array($segStr, $segments, true)) {
                    $segments[] = $segStr;
                }
            }

            if ($days === [] || $segments === []) {
                continue;
            }

            $cents = max(0, (int) ($rule['hourly_price_cents'] ?? 0));
            $label = trim((string) ($rule['label'] ?? ''));

            $normalized[] = [
                'label' => $label,
                'days' => $days,
                'segments' => $segments,
                'hourly_price_cents' => $cents,
            ];
        }

        return $normalized;
    }

    public function hasRentalRules(): bool
    {
        return $this->normalizedRentalRules() !== [];
    }

    /**
     * Retourne la première plage personnalisée correspondant à la date et au créneau.
     *
     * @return array{label: string, days: array<int, int>, segments: array<int, string>, hourly_price_cents: int}|null
     */
    public function matchingRentalRule(Carbon $date, string $segment): ?array
    {
        $dayOfWeek = (int) $date->dayOfWeek;

        foreach ($this->normalizedRentalRules() as $rule) {
            if (in_array($dayOfWeek, $rule['days'], true) && in_array($segment, $rule['segments'], true)) {
                return $rule;
            }
        }

        return null;
    }

    public function isSegmentAllowedByRentalRules(Carbon $date, string $segment): bool
    {
        if (! $this->hasRentalRules()) {
            return true;
        }

        return $this->matchingRentalRule($date, $segment) !== null;
    }

    /**
     * Retourne les créneaux autorisés par les plages personnalisées pour une date donnée.
     *
     * @return array<int, string>
     */
    public function allowedSegmentsForDate(Carbon $date): array
    {
        $rules = $this->normalizedRentalRules();
        if ($rules === []) {
            return array_keys(BookingPeriod::SEGMENTS);
        }

        $dayOfWeek = (int) $date->dayOfWeek;
        $allowed = [];

        foreach ($rules as $rule) {
            if (in_array($dayOfWeek, $rule['days'], true)) {
                foreach ($rule['segments'] as $segment) {
                    $allowed[$segment] = true;
                }
            }
        }

        return array_keys($allowed);
    }

    public function hourlyPriceCentsFor(Carbon $date, string $segment): int
    {
        $rule = $this->matchingRentalRule($date, $segment);
        if ($rule !== null) {
            return $rule['hourly_price_cents'];
        }

        return (int) ($this->hourly_price_cents ?? 0);
    }

    public function quoteCentsFor(Carbon $date, string $segment): int
    {
        return BookingPeriod::amount($this->hourlyPriceCentsFor($date, $segment), $segment);
    }

    public function isFreeFor(Carbon $date, string $segment): bool
    {
        return $this->quoteCentsFor($date, $segment) === 0;
    }

    public function minHourlyPriceCents(): int
    {
        $rules = $this->normalizedRentalRules();
        if ($rules === []) {
            return (int) ($this->hourly_price_cents ?? 0);
        }

        return (int) min(array_column($rules, 'hourly_price_cents'));
    }

    public function maxHourlyPriceCents(): int
    {
        $rules = $this->normalizedRentalRules();
        if ($rules === []) {
            return (int) ($this->hourly_price_cents ?? 0);
        }

        return (int) max(array_column($rules, 'hourly_price_cents'));
    }

    public function priceSummaryLabel(): string
    {
        $min = $this->minHourlyPriceCents();
        $max = $this->maxHourlyPriceCents();

        if ($min === $max) {
            return $min === 0 ? 'Gratuit' : format_chf($min / 100).'/h';
        }

        $minLabel = $min === 0 ? 'Gratuit' : format_chf($min / 100).'/h';
        $maxLabel = format_chf($max / 100).'/h';

        return $minLabel.' – '.$maxLabel;
    }

    /**
     * Résumé lisible des plages personnalisées pour l'affichage sur les cartes.
     *
     * @return array<int, array{label: string, days: string, segments: string, price: string, hourly_price_cents: int, is_free: bool}>
     */
    public function rentalRuleSummaries(): array
    {
        return collect($this->normalizedRentalRules())
            ->map(function (array $rule): array {
                $daysText = self::formatDaysLabel($rule['days']);
                $segmentsText = self::formatSegmentsLabel($rule['segments']);
                $isFree = $rule['hourly_price_cents'] === 0;

                return [
                    'label' => $rule['label'] !== '' ? $rule['label'] : $daysText,
                    'days' => $daysText,
                    'segments' => $segmentsText,
                    'price' => $isFree ? 'Gratuit' : format_chf($rule['hourly_price_cents'] / 100).'/h',
                    'hourly_price_cents' => $rule['hourly_price_cents'],
                    'is_free' => $isFree,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, string>  $segments
     */
    public static function formatSegmentsLabel(array $segments): string
    {
        if (in_array('journee', $segments, true)
            && in_array('matin_travail', $segments, true)
            && in_array('aprem_travail', $segments, true)) {
            $others = array_values(array_diff($segments, ['journee', 'matin_travail', 'aprem_travail']));
            $labels = ['Journée'];
            foreach ($others as $seg) {
                $labels[] = BookingPeriod::SEGMENTS[$seg]['label'] ?? $seg;
            }

            return implode(', ', $labels);
        }

        return collect($segments)
            ->map(fn (string $seg) => BookingPeriod::SEGMENTS[$seg]['label'] ?? $seg)
            ->implode(', ');
    }

    /**
     * @param  array<int, int>  $days
     */
    public static function formatDaysLabel(array $days): string
    {
        $sorted = array_values(array_unique($days));
        sort($sorted);

        if ($sorted === [0, 1, 2, 3, 4, 5, 6]) {
            return '7j/7';
        }
        if ($sorted === [1, 2, 3, 4, 5]) {
            return 'Lun–Ven';
        }
        if ($sorted === [0, 6]) {
            return 'Sam–Dim';
        }

        $orderedKeys = [1, 2, 3, 4, 5, 6, 0];
        $labels = [];
        foreach ($orderedKeys as $dayIndex) {
            if (in_array($dayIndex, $sorted, true)) {
                $labels[] = self::DAY_SHORT_LABELS[$dayIndex];
            }
        }

        return implode(', ', $labels);
    }

    public function hasWeeklySchedule(): bool
    {
        return in_array($this->weekly_schedule_type, ['full_week', 'work_week'], true);
    }

    /**
     * @return array<int>
     */
    public function weeklyScheduleDayIndexes(): array
    {
        return match ($this->weekly_schedule_type) {
            'full_week' => [1, 2, 3, 4, 5, 6, 0],
            'work_week' => [1, 2, 3, 4, 5],
            default => [],
        };
    }

    public function weeklyScheduleLabel(): ?string
    {
        return match ($this->weekly_schedule_type) {
            'full_week' => 'Semaine complète',
            'work_week' => 'Semaine de travail',
            default => null,
        };
    }

    public function isVisibleWithGroupCode(?string $groupCode): bool
    {
        return $this->isVisibleWithAnyGroupCodes(
            empty($groupCode) ? [] : [$groupCode],
            null
        );
    }

    /**
     * @param  array<int, string>  $groupCodes
     */
    public function isVisibleWithAnyGroupCodes(array $groupCodes, ?string $userEmail = null): bool
    {
        if (! $this->is_group_reserved) {
            return true;
        }

        if ($this->isVisibleForAllowedEmailDomain($userEmail)) {
            return true;
        }

        if (empty($this->group_access_code_hash)) {
            return false;
        }

        foreach ($groupCodes as $groupCode) {
            if (! empty($groupCode) && Hash::check((string) $groupCode, $this->group_access_code_hash)) {
                return true;
            }
        }

        return false;
    }

    public function isVisibleForAllowedEmailDomain(?string $userEmail): bool
    {
        if (! $this->is_group_reserved || empty($userEmail)) {
            return false;
        }

        $domains = collect($this->group_allowed_email_domains ?? [])
            ->map(fn ($domain) => ltrim(mb_strtolower(trim((string) $domain)), '@'))
            ->filter(fn ($domain) => $domain !== '')
            ->values();

        if ($domains->isEmpty()) {
            return false;
        }

        $emailDomain = mb_strtolower((string) strstr($userEmail, '@'));
        $emailDomain = ltrim($emailDomain, '@');

        return $emailDomain !== '' && $domains->contains($emailDomain);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PlaceAvailability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->blockedSlots();
    }

    /**
     * @return HasMany<PlaceAvailability, $this>
     */
    public function blockedSlots(): HasMany
    {
        return $this->hasMany(PlaceAvailability::class);
    }

    /**
     * @return HasMany<PlaceUnavailability, $this>
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(PlaceUnavailability::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function feedbacks(): HasManyThrough
    {
        return $this->hasManyThrough(Feedback::class, Reservation::class);
    }

    /**
     * Vérifie si une place est disponible pour un créneau donné (avec exceptions).
     */
    public function isAvailableFor(Carbon $start, Carbon $end): bool
    {
        return $this->isReservableFor($start, $end);
    }

    public function isReservableFor(Carbon $start, Carbon $end): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->availability_start_date && $start->lt($this->availability_start_date->startOfDay())) {
            return false;
        }
        if ($this->availability_end_date && $end->gt($this->availability_end_date->endOfDay())) {
            return false;
        }

        if ($start->toDateString() !== $end->toDateString()) {
            return false;
        }

        if ($this->hasRentalRules()) {
            $detectedSegment = BookingPeriod::detectSegment($start, $end);
            if (! $this->isSegmentAllowedByRentalRules($start, $detectedSegment)) {
                return false;
            }
        }

        $day = (int) $start->dayOfWeek; // 0 (dim) -> 6 (sam)
        $weeklyBlockedSlots = $this->blockedSlots->where('day_of_week', $day);

        foreach ($weeklyBlockedSlots as $slot) {
            $slotStart = Carbon::parse($start->toDateString().' '.$slot->start_time);
            $slotEnd = Carbon::parse($start->toDateString().' '.$slot->end_time);
            if ($slotEnd->lessThanOrEqualTo($slotStart)) {
                continue;
            }
            if ($start < $slotEnd && $end > $slotStart) {
                return false;
            }
        }

        $exceptions = $this->unavailabilities->filter(fn ($exception) => $exception->date->toDateString() === $start->toDateString());
        foreach ($exceptions as $exception) {
            if (empty($exception->start_time) && empty($exception->end_time)) {
                return false;
            }
            $exStart = Carbon::parse($start->toDateString().' '.$exception->start_time);
            $exEnd = Carbon::parse($start->toDateString().' '.$exception->end_time);
            if ($exEnd->lessThanOrEqualTo($exStart)) {
                continue;
            }
            if ($start < $exEnd && $end > $exStart) {
                return false;
            }
        }

        return true;
    }

    /**
     * Indique si la place est ouverte pour une date (au moins un créneau).
     */
    public function hasAvailabilityForDate(Carbon $date): bool
    {
        return $this->isReservableForDate($date);
    }

    public function isReservableForDate(Carbon $date): bool
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        if ($this->availability_start_date && $dayStart->lt($this->availability_start_date->startOfDay())) {
            return false;
        }
        if ($this->availability_end_date && $dayEnd->gt($this->availability_end_date->endOfDay())) {
            return false;
        }

        if ($this->hasRentalRules() && $this->allowedSegmentsForDate($date) === []) {
            return false;
        }

        $fullDayBlock = $this->unavailabilities->contains(fn ($exception) => $exception->date->toDateString() === $date->toDateString()
            && empty($exception->start_time) && empty($exception->end_time)
        );

        return ! $fullDayBlock;
    }
}
