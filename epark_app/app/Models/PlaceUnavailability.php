<?php

namespace App\Models;

use Database\Factories\PlaceUnavailabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $place_id
 * @property Carbon $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $reason
 */
class PlaceUnavailability extends Model
{
    /** @use HasFactory<PlaceUnavailabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'place_id',
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
