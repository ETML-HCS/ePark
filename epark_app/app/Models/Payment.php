<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'reservation_id',
        'amount_cents',
        'provider',
        'provider_status',
        'provider_ref',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
