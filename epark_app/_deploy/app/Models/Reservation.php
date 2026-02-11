<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Reservation
 *
 * @property int $id
 * @property int $user_id
 * @property int $place_id
 * @property string $statut
 * @property Carbon $date_debut
 * @property Carbon $date_fin
 * @property Carbon|null $actual_end_at
 * @property Carbon|null $end_reminder_sent_at
 * @property int $battement_minutes
 * @property int $amount_cents
 * @property int $overstay_minutes
 * @property int $penalty_cents
 * @property string $payment_status
 * @property bool $paiement_effectue
 * @property Place $place
 * @property User $user
 * @property Payment|null $payment
 */
class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'place_id',
        'date_debut',
        'date_fin',
        'statut',
        'battement_minutes',
        'amount_cents',
        'payment_status',
        'paiement_effectue',
        'owner_message',
        'actual_end_at',
        'overstay_minutes',
        'penalty_cents',
        'end_reminder_sent_at',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'battement_minutes' => 'integer',
        'amount_cents' => 'integer',
        'paiement_effectue' => 'boolean',
        'actual_end_at' => 'datetime',
        'overstay_minutes' => 'integer',
        'penalty_cents' => 'integer',
        'end_reminder_sent_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Filtre les réservations en attente.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Filtre les réservations terminées (confirmées et passées).
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('statut', 'confirmée')
            ->where('date_fin', '<', now());
    }

    /**
     * Filtre les réservations confirmées.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('statut', 'confirmée');
    }

    /**
     * Filtre les réservations actives (non annulées).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('statut', ['annulée']);
    }

    /**
     * Filtre les réservations pour une place donnée.
     */
    public function scopeForPlace(Builder $query, int $placeId): Builder
    {
        return $query->where('place_id', $placeId);
    }

    /**
     * Filtre les réservations pour un propriétaire de places.
     */
    public function scopeForOwner(Builder $query, int $userId): Builder
    {
        return $query->whereHas('place', fn($q) => $q->where('user_id', $userId));
    }

    // -------------------------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------------------------

    /**
     * Calcule la date de fin effective incluant le battement.
     */
    public function getEffectiveEndTime(): Carbon
    {
        return $this->date_fin->copy()->addMinutes($this->battement_minutes ?? 0);
    }

    /**
     * Calcule la penalite de depassement en centimes.
     */
    public function calculateOverstayPenaltyCents(?Carbon $actualEnd = null): int
    {
        $effectiveEnd = $this->getEffectiveEndTime();
        $actual = $actualEnd ?? now();
        $overstayMinutes = $effectiveEnd->diffInMinutes($actual, false);

        if ($overstayMinutes < 60) {
            return 0;
        }

        if ($overstayMinutes >= 24 * 60) {
            return 12000;
        }

        if ($overstayMinutes >= 3 * 60) {
            return 8000;
        }

        return 4000;
    }

    /**
     * Vérifie si la réservation est payée.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Vérifie si la réservation peut être confirmée.
     */
    public function canBeConfirmed(): bool
    {
        return $this->statut === 'en_attente' && $this->isPaid();
    }

    /**
     * Vérifie si un créneau chevauche des réservations existantes pour une place donnée.
     * Utilise une requête SQL optimisée au lieu de charger toutes les réservations en mémoire.
     */
    public static function overlaps(int $placeId, Carbon $start, Carbon $end, int $battementMinutes = 0, ?int $excludeId = null): bool
    {
        $newEnd = $end->copy()->addMinutes($battementMinutes);

        // Requête SQL optimisée avec calcul du battement directement en base
        $builder = self::query()
            ->where('place_id', $placeId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->active();

        $connection = $builder->getConnection();
        $driver = $connection->getDriverName();

        $startStr = $start->format('Y-m-d H:i:s');
        $newEndStr = $newEnd->format('Y-m-d H:i:s');

        // Chevauchement: existing.date_debut < new.end AND existing.effective_end > new.start
        if ($driver === 'sqlite') {
            // SQLite: use datetime() with minutes modifier
            $builder->where(function ($query) use ($startStr, $newEndStr) {
                $query->where('date_debut', '<', $newEndStr)
                    ->whereRaw("datetime(date_fin, '+' || COALESCE(battement_minutes, 0) || ' minutes') > ?", [$startStr]);
            });
        } else {
            // MySQL / MariaDB: use DATE_ADD
            $builder->where(function ($query) use ($startStr, $newEndStr) {
                $query->where('date_debut', '<', $newEndStr)
                    ->whereRaw('DATE_ADD(date_fin, INTERVAL COALESCE(battement_minutes, 0) MINUTE) > ?', [$startStr]);
            });
        }

        return $builder->exists();
    }
}
