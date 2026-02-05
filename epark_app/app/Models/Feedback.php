<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Feedback
 *
 * @property int $id
 * @property int $reservation_id
 * @property int $user_id
 * @property int $rating
 * @property string|null $comment
 */
class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
