<?php

namespace App\Models;

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
 */
class Place extends Model
{
    /** @use HasFactory<PlaceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', // propriétaire
        'site_id',
        'nom',
        'type',
        'dimensions_json',
        'equipments_json',
        'hourly_price_cents',
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
        'is_active' => 'boolean',
        'cancel_deadline_hours' => 'integer',
        'pending_validation_default' => 'string',
        'availability_start_date' => 'date',
        'availability_end_date' => 'date',
        'disponible' => 'boolean',
        'is_group_reserved' => 'boolean',
        'group_allowed_email_domains' => 'array',
    ];

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

        $fullDayBlock = $this->unavailabilities->contains(fn ($exception) => $exception->date->toDateString() === $date->toDateString()
            && empty($exception->start_time) && empty($exception->end_time)
        );

        return ! $fullDayBlock;
    }
}
