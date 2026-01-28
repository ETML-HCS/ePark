<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = [
        'user_id', // propriétaire
        'site_id',
        'nom',
        'type',
        'dimensions_json',
        'equipments_json',
        'hourly_price_cents',
        'is_active',
        'adresse',
        'description',
        'disponible', // temporaire, à remplacer par is_active
        'caracteristiques',
    ];

    protected $casts = [
        'dimensions_json' => 'array',
        'equipments_json' => 'array',
        'hourly_price_cents' => 'integer',
        'is_active' => 'boolean',
        'disponible' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
