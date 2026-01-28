<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'place_id',
        'date_debut',
        'date_fin',
        'statut',
        'battement_minutes',
        'paiement_effectue', // paiement optionnel
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'battement_minutes' => 'integer',
        'paiement_effectue' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Vérifie si un créneau chevauche des réservations existantes pour une place donnée.
     * Prend en compte les battements (marge) demandés et accordés.
     *
     * @param int $placeId
     * @param \Illuminate\Support\Carbon $start
     * @param \Illuminate\Support\Carbon $end
     * @param int $battementMinutes battement demandé pour la nouvelle réservation
     * @param int|null $excludeId id de réservation à exclure (pour update)
     * @return bool
     */
    public static function overlaps(int $placeId, $start, $end, int $battementMinutes = 0, ?int $excludeId = null): bool
    {
        $newEnd = $end->copy()->addMinutes($battementMinutes);

        $reservations = self::where('place_id', $placeId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->get();

        foreach ($reservations as $r) {
            $existingStart = $r->date_debut;
            $existingEnd = $r->date_fin->copy();
            if (!empty($r->battement_minutes)) {
                $existingEnd = $existingEnd->addMinutes($r->battement_minutes);
            }

            if ($existingStart < $newEnd && $existingEnd > $start) {
                return true;
            }
        }

        return false;
    }
}
