<?php

namespace App\Models;

use Database\Factories\PlaceAvailabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $place_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 */
class PlaceAvailability extends Model
{
    /** @use HasFactory<PlaceAvailabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'place_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
