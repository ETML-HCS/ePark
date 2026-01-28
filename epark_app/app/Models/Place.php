<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = [
        'user_id', // propriétaire
        'site_id',
        'nom',
        'adresse',
        'description',
        'disponible',
        'caracteristiques',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
