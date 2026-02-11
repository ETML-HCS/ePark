<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Place
 *
 * @property string $nom
 */
class Place extends Model
{
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
        'availability_start_date',
        'availability_end_date',
        'adresse',
        'description',
        'disponible', // temporaire, à remplacer par is_active
        'caracteristiques',
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
        'availability_start_date' => 'date',
        'availability_end_date' => 'date',
        'disponible' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(PlaceAvailability::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(PlaceUnavailability::class);
    }

    /**
     * Vérifie si une place est disponible pour un créneau donné (avec exceptions).
     */
    public function isAvailableFor(Carbon $start, Carbon $end): bool
    {
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
        $blockedSlots = $this->availabilities()->where('day_of_week', $day)->get();

        foreach ($blockedSlots as $slot) {
            $slotStart = Carbon::parse($start->toDateString().' '.$slot->start_time);
            $slotEnd = Carbon::parse($start->toDateString().' '.$slot->end_time);
            if ($slotEnd->lessThanOrEqualTo($slotStart)) {
                continue;
            }
            if ($start < $slotEnd && $end > $slotStart) {
                return false;
            }
        }

        $exceptions = $this->unavailabilities()->whereDate('date', $start->toDateString())->get();
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
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        if ($this->availability_start_date && $dayStart->lt($this->availability_start_date->startOfDay())) {
            return false;
        }
        if ($this->availability_end_date && $dayEnd->gt($this->availability_end_date->endOfDay())) {
            return false;
        }

        $fullDayBlock = $this->unavailabilities()
            ->whereDate('date', $date->toDateString())
            ->whereNull('start_time')
            ->whereNull('end_time')
            ->exists();

        return !$fullDayBlock;
    }
}
